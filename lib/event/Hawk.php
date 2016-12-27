<?php
namespace Freischutz\Event;

use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Event\Hawk
 */
class Hawk extends Component
{
    private $version = '0.2.0';
    private $nonceFile = 'freischutz.hawk.nonce';
    private $params;
    private $key;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Set authentication parameters
        $params = array();
        foreach (explode(',', $this->request->getHeader('Authorization')) as $param) {
            $set = explode('=', trim($param));
            $params[$set[0]] = isset($set[1]) ? trim($set[1], "'\"") : true;
        }
        $params['ext'] = isset($params['ext']) ? $params['ext'] : false;
        $this->params = (object) $params;
    }

    /**
     * Get request authentication parameter(s).
     *
     * Get a single parameter $param, or all parameters if $param not given.
     *
     * @param string $param (optional) Parameter name.
     * @return mixed
     */
    public function getParam($param = false)
    {
        if ($param && isset($this->params->$param)) {
            $result = $this->params->$param;
        } elseif ($param && !isset($this->params->$param)) {
            $result = null;
        } else {
            $result = $this->params;
        }

        return $result;
    }

    /**
     * Set client key.
     *
     * @return string
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get Freischutz\Event\Hawk version.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Authenticate client request.
     *
     * @return object
     */
    public function authenticate()
    {
        $result = (object) array('state' => false, 'message' => null);
        if (empty($this->key)) {
            $result->message = 'User denied.';
            return $result;
        }

        // Get allowed algorithms
        $algorithms = array_map(
            'trim',
            explode(',', $this->config->hawk->get('algorithms', 'sha256'))
        );

        if (!isset($this->params->alg) || empty($this->params->alg)) {
            /**
             * No algorithm requested, use default algorithm
             */
             $alg = $algorithms[0];
        } elseif (!in_array($this->params->alg, $algorithms, true)) {
            /**
             * Requested algorithm not allowed
             */
            $result->message = 'Algorithm not allowed.';
            return $result;
        } else {
            /**
             * Use requested algorithm
             */
             $alg = $this->params->alg;
        }

        // Check nonce
        if ($this->lookupNonce($this->params->nonce)) {
            $result->message = 'Duplicate nonce.';
            return $result;
        }

        // Save nonce
        $this->manageNonces($this->params->nonce);

        // Create payload string
        $payload = 'hawk.1.payload\n' .
                   $this->request->getContentType() . '\n' .
                   $this->data->getRaw() . '\n';
        $hash = hash($alg, $payload);

        // Create request string
        $message = 'hawk.1.header\n' .
                   $this->params->ts . '\n' .
                   $this->params->nonce . '\n' .
                   $this->request->getMethod() . '\n' .
                   $this->request->getURI() . '\n' .
                   $this->request->getHttpHost() . '\n' .
                   $this->request->getPort() . '\n' .
                   $hash . '\n' .
                   $this->params->ext . '\n';

        // Create MAC for comparison
        $serverMac = hash_hmac($alg, $message, $this->key);

        /**
         * Authenticate
         */
        if ($serverMac === $this->params->mac) {
            // Message is authentic
            $expire = $this->config->hawk->get('expire', 60);
            if ((time() - $this->params->ts) <= $expire) {
                // Message is valid
                if ($hash === $this->params->hash) {
                    // Payload hash is correct
                    $result->state = true;
                } else {
                    $result->message = 'Payload mismatch.';
                }
            } else {
                $result->message = 'Request expired.';
            }
        } else {
            $result->message = 'Request not authentic.';
        }

        return $result;
    }

    /**
     * Create Server-Authorization header value for server response.
     *
     * Only usable in response to a request successfully validated by
     * Freischutz\Event\hawk::authenticate().
     *
     * @param string $ext (optional) Value for "ext=" in Server-Authorization
     *    header.
     * @throw \Exception when used without a validated request.
     * @return string Server-Authorization header string.
     */
    public function validateResponse($ext = false)
    {
        if (!$params || !$key) {
            throw new \Exception();
        }

        $payload = "hawk.1.payload\n" .
                   $this->response->getContentType() . "\n" .
                   $this->response->getContent() . "\n";
        $hash = hash($this->params->alg, $payload);

        // Create response string
        $message = "hawk.1.header\n" .
                   $this->params->ts . "\n" .
                   $this->params->nonce . "\n" .
                   $this->request->getMethod() . "\n" .
                   $this->request->getURI() . "\n" .
                   $this->request->getHttpHost() . "\n" .
                   $this->request->getPort() . "\n" .
                   $hash . "\n" .
                   $ext . "\n";

        // Create MAC
        $mac = hash_hmac($alg, $message, $this->key);
        $extSet = $ext ? ", ext=$ext" : '';

        return "Server-Authorization: Hawk mac=$mac, hash=$hash" . $extSet;
    }

    /**
     * Record used nonce and forget expired nonces.
     *
     * @param string $nonce Nonce to record.
     * @return void
     */
    private function manageNonces($nonce)
    {
        $list = array();
        $timestamp = date('U');
        $file = $this->config->hawk->get('nonce_file', '/tmp') . '/' . $this->nonceFile;
        if (file_exists($file)) {
            /**
             * Manage recorded nonces
             */
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $handle = fopen($file, 'w');
            foreach ($lines as $line) {
                $parts = explode(',', $line);
                if (sizeof($parts) === 2) {
                    if ($parts[1] + $this->config->hawk->get('expire', '60') < $timestamp) {
                        /**
                         * Forget expired nonces
                         */
                        continue;
                    }
                    fwrite($handle, $line . "\n");
                } else {
                    throw new \Exception("Malformed row in $file: $line");
                }
            }
        } else {
            $handle = fopen($file, 'w');
        }
        // Record new nonce
        fwrite($handle, "$nonce,$timestamp\n");
        fclose($handle);
        chmod($file, 0755);
    }

    /**
     * Check if nonce has been used previously.
     *
     * @param string $nonce Nonce to lookup.
     * @return bool
     */
    private function lookupNonce($nonce)
    {
        $result = $this->lookupNonceInFile($nonce);
        return $result;
    }

    /**
     * Check if nonce is recorded in file.
     *
     * @param string $nonce Nonce to lookup.
     * @return bool
     */
    private function lookupNonceInFile($nonce)
    {
        $file = $this->config->hawk->get('nonce_file', '/tmp') . '/' . $this->nonceFile;
        if (!file_exists($file)) {
            return false;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $parts = explode(',', $line);
            if (sizeof($parts) === 2) {
                if ($parts[0] === $nonce) {
                    return true;
                }
            } else {
                throw new \Exception("Malformed row in $file: $line");
            }
        };

        return false;
    }
}
