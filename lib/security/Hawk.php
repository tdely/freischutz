<?php
namespace Freischutz\Security;

use Phalcon\Mvc\Model\Query;
use Phalcon\Mvc\User\Component;

/**
 * Freischutz\Security\Hawk
 *
 * Implementation of the Hawk protocol.
 * HTTP HMAC authentication with partial cryptographic verification of request,
 * which covers method, URI, host and port, various other authentication
 * details, and payload. Optionally allows for verification of response.
 */
class Hawk extends Component
{
    private $backend;
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
        foreach (str_getcsv($this->request->getHeader('Authorization'), ',', '"') as $param) {
            $set = str_getcsv(trim($param), '=', '"');
            $params[$set[0]] = isset($set[1]) ? trim($set[1], "'\"") : true;
        }
        $params['ext'] = isset($params['ext']) ? $params['ext'] : false;
        $this->params = (object) $params;

        // Set backend
        $this->backend = strtolower($this->config->hawk->get('storage', 'file'));
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
        $serverMac = base64_encode(hash_hmac($alg, $message, $this->key, true));

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
     *   header.
     * @throw \Exception if $params or $key properties not set, such as when
     *   used before calling authenticate().
     * @return string Server-Authorization header string.
     */
    public function validateResponse($ext = false)
    {
        if (!$params || !$key) {
            throw new \Exception('');
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
        syslog(LOG_DEBUG, hash_hmac($alg, $message, $this->key));
        $mac = base64_encode(hash_hmac($alg, $message, $this->key));
        syslog(LOG_DEBUG, $mac);
        $extSet = $ext ? ", ext=$ext" : '';

        return "Server-Authorization: Hawk mac=$mac, hash=$hash" . $extSet;
    }

    /**
     * Record used nonce and forget expired nonces.
     *
     * @param string $nonce Nonce to record.
     * @throw \Exception on unknown backend.
     * @return void
     */
    private function manageNonces($nonce)
    {
        switch ($this->backend) {
            case 'file':
                $this->manageNonceFile($nonce);
                break;
            case 'database':
            case 'db':
                $this->manageNonceDatabase($nonce);
                break;
            default:
                throw new \Exception("Unknown Hawk backend: $backend");
        }
    }

    /**
     * Record used nonce and forget expired nonces in file.
     *
     * @param string $nonce Nonce to record.
     * @throw \Exception if encountering a malformed line.
     * @return void
     */
    private function manageNonceFile($nonce)
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
                    if ($parts[1] + $this->config->hawk->get('expire', 60) < $timestamp) {
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
     * Record used nonce and forget expired nonces in database.
     *
     * @param string $nonce Nonce to record.
     * @throw \Exception if nonce_model not set in config hawk section.
     * @throw \Exception if model cannot be found.
     * @throw \Exception if model fails to save.
     * @return void
     */
    private function manageNonceDatabase($nonce)
    {
        $timestamp = date('U');
        if (!isset($this->config->hawk)
                || !isset($this->config->hawk->nonce_model)) {
            throw new \Exception(
                "Nonce backend 'database' requires nonce_model set in hawk " .
                "section in config file."
            );
        }

        $modelName = $this->config->hawk->nonce_model;

        if (!class_exists($modelName)) {
            throw new \Exception("Nonce model not found: " . $modelName);
        }

        $model = new $modelName;
        $metadata = $model->getModelsMetaData();

        $result = $this->modelsManager->executeQuery(
            "DELETE FROM $modelName WHERE (timestamp + :expire:) < :timestamp:",
            array(
                'expire' => $this->config->hawk->get('expire', 60),
                'timestamp' => $timestamp,
            )
        );

        $model->nonce = $nonce;
        $model->timestamp = $timestamp;
        if (!$model->save()) {
            throw new \Exception("Could not save nonce to database");
        }
    }

    /**
     * Check if nonce has been used previously.
     *
     * @param string $nonce Nonce to lookup.
     * @throw \Exception on unknown backend.
     * @return bool
     */
    private function lookupNonce($nonce)
    {
        switch ($this->backend) {
            case 'file':
                $result = $this->lookupNonceInFile($nonce);
                break;
            case 'database':
            case 'db':
                $result = $this->lookupNonceInDatabase($nonce);
                break;
            default:
                throw new \Exception("Unknown nonce backend: $backend");
        }
        return $result;
    }

    /**
     * Check if nonce is recorded in file.
     *
     * @param string $nonce Nonce to lookup.
     * @throw \Exception if encountering a malformed line.
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

    /**
     * Check if nonce is recorded in database.
     *
     * @param string $nonce Nonce to lookup.
     * @throw \Exception if nonce_model not set in config hawk section.
     * @throw \Exception if model cannot be found.
     * @throw \Exception if model doesn't contain attributes nonce and timestamp.
     * @return bool
     */
    private function lookupNonceInDatabase($nonce)
    {
        if (!isset($this->config->hawk)
                || !isset($this->config->hawk->nonce_model)) {
            throw new \Exception(
                "Nonce backend 'database' requires nonce_model set in hawk " .
                "section in config file."
            );
        }

        $modelName = $this->config->hawk->nonce_model;

        if (!class_exists($modelName)) {
            throw new \Exception("Nonce model not found: " . $modelName);
        }

        $model = new $modelName;
        $metadata = $model->getModelsMetaData();

        if (!$metadata->hasAttribute($model, 'nonce')
                || !$metadata->hasAttribute($model, 'timestamp')) {
            throw new \Exception(
                "Nonce model must contain columns 'nonce' and 'timestamp'."
            );
        }
        $search = array("nonce=':nonce:'", array('nonce' => $nonce));
        return $model->findFirst($search) ? true : false;
    }
}
