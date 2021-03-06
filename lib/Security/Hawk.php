<?php
namespace Freischutz\Security;

use Freischutz\Application\Exception;
use Phalcon\Mvc\User\Component;
use stdClass;

/**
 * Hawk authentication.
 *
 * Implementation of the Hawk protocol.
 * HTTP HMAC authentication with partial cryptographic verification of request,
 * which covers method, URI, host and port, various other authentication
 * details, and payload. Optionally allows for verification of response.
 *
 * @see       https://gitlab.com/tdely/freischutz/ Freischutz on GitLab
 * @see       https://github.com/hueniverse/hawk The original Hawk on GitHub
 *
 * @author    Tobias Dély (tdely) <cleverhatcamouflage@gmail.com>
 * @copyright 2017-present Tobias Dély
 * @license   https://directory.fsf.org/wiki/License:BSD-3-Clause BSD-3-Clause
 */
class Hawk extends Component
{
    /** @var string Backend type to use for storing nonces. */
    private $backend;
    /** @var string File name to store nonces in when using file backend. */
    private $nonceFile = 'freischutz.hawk.nonce';
    /** @var string Key name to store nonce under in cache. */
    private $nonceCacheKey;
    /** @var stdClass Hawk parameters from request. */
    private $params;
    /** @var string HMAC key. */
    private $key;

    /**
     * Hawk constructor.
     */
    public function __construct()
    {
        // Strip 'Hawk ' from header
        $header = substr($this->request->getHeader('Authorization'), 5);

        // Set authentication parameters
        $params = array(
            "id" => false,
            "ts" => false,
            "nonce" => false,
            "mac" => false,
            "hash" => false,
            "ext" => false,
            "alg" => false
        );
        foreach (str_getcsv($header, ',', '"') as $param) {
            $set = str_getcsv(trim($param), '=', '"');
            $params[$set[0]] = isset($set[1]) ? trim($set[1], "'\"") : true;
        }
        $this->params = (object) $params;

        $this->nonceCacheKey = '_freischutz_nonce_' . $params['nonce'];

        // Set backend
        $this->backend = strtolower(
            $this->config->hawk->get('backend', 'file')
        );
    }

    /**
     * Get request authentication parameter(s).
     *
     * Get a single parameter $param, or all parameters if $param not given.
     *
     * @internal
     * @param string|false $param (optional) Parameter name.
     * @return \stdClass|null
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
     * @internal
     * @param string $key Client key.
     * @return void
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * Authenticate client request.
     *
     * @internal
     * @throws \Freischutz\Application\Exception
     * @return \stdClass
     */
    public function authenticate():stdClass
    {
        $result = (object) array('state' => false, 'message' => null);
        if (empty($this->key)) {
            $this->logger->debug(
                "[Hawk] No key set for user ID {$this->params->id}"
            );
            $result->message = 'User has no key.';
            return $result;
        }

        // Get allowed algorithms
        $algorithms = array_map(
            'trim',
            explode(',', $this->config->hawk->get('algorithms', 'sha256'))
        );

        if (!preg_match('/alg=([[:alnum:]]+)/', $this->params->ext, $matches)) {
            // No algorithm requested, use default algorithm
            $this->params->alg = $algorithms[0];
        } elseif (!in_array(strtolower($matches[1]), $algorithms, true)) {
            // Requested algorithm not allowed
            $result->message = 'Algorithm not allowed.';
            return $result;
        } else {
            // Use requested algorithm
            $this->params->alg = $matches[1];
        }

        // Check nonce
        if ($this->lookUpNonce($this->params->nonce)) {
            $result->message = 'Duplicate nonce.';
            return $result;
        }

        // Save nonce
        $this->manageNonces($this->params->nonce);

        // Create payload string
        if ($this->params->hash !== false) {
            $payload = "hawk.1.payload\n" .
                       $this->request->getContentType() . "\n" .
                       $this->data->getRaw() . "\n";
            $hash = base64_encode(hash($this->params->alg, $payload, true));
        } else {
            $hash = '';
        }

        // Create request string
        $message = "hawk.1.header\n" .
                   $this->params->ts . "\n" .
                   $this->params->nonce . "\n" .
                   $this->request->getMethod() . "\n" .
                   $this->request->getURI() . "\n" .
                   $this->request->getHttpHost() . "\n" .
                   $this->request->getPort() . "\n" .
                   $hash . "\n" .
                   $this->params->ext . "\n";

        // Create MAC for comparison
        $serverMac = base64_encode(
            hash_hmac($this->params->alg, $message, $this->key, true)
        );

        /**
         * Authenticate
         */
        $now = time();
        if ($serverMac === $this->params->mac) {
            // Message is authentic
            $expire = $this->config->hawk->get('expire', 60);
            $timedelta = ($this->params->ts - $now);
            if ($timedelta <= $expire
                    && (-1 * $timedelta) <= $expire) {
                // Message is valid
                if (isset($this->params->hash)
                        && $hash === $this->params->hash) {
                    // Payload hash is correct
                    $result->state = true;
                    $this->logger->debug("[Hawk] OK.");
                } elseif ($this->params->hash === false) {
                    // Payload not included in validation
                    $result->state = true;
                    $this->logger->debug(
                        "[Hawk] OK (Payload hash omitted by client)."
                    );
                } else {
                    $result->message = 'Payload mismatch.';
                    $this->logger->debug(
                        "[Hawk] Payload mismatch: expected $hash, got " .
                        "{$this->params->hash}."
                    );
                }
            } elseif (($this->params->ts - $now) > $expire) {
                $result->message = 'Request too far into future.';
                $this->logger->debug(
                    "[Hawk] Timedelta threshold exceeded: $timedelta " .
                    "(threshold ±$expire)."
                );
            } else {
                $result->message = 'Request expired.';
                $this->logger->debug(
                    "[Hawk] Timedelta threshold exceeded: $timedelta " .
                    "(threshold ±$expire)."
                );
            }
        } else {
            $result->message = 'Request not authentic.';
            $this->logger->debug(
                "[Hawk] MAC mismatch: expected $serverMac, got " .
                "{$this->params->mac}."
            );
        }

        return $result;
    }

    /**
     * Create Server-Authorization header value for server response.
     *
     * Only usable in response to a request successfully validated by
     * Freischutz\Event\hawk::authenticate().
     *
     * @internal
     * @throws \Freischutz\Application\Exception
     * @param string|false $ext (optional) Value for ext ('ext="$value"' in
     *   Server-Authorization header.
     * @return string
     */
    public function validateResponse($ext = false):string
    {
        if (empty($this->params) || empty($this->key)) {
            throw new Exception("Properties 'params' and 'key' not set");
        }

        $payload = "hawk.1.payload\n" .
                   $this->response->getContentType() . "\n" .
                   $this->response->getContent() . "\n";
        $hash = base64_encode(hash($this->params->alg, $payload, true));

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
        $mac = base64_encode(
            hash_hmac($this->params->alg, $message, $this->key, true)
        );
        $extSet = $ext ? ", \"ext=$ext\"" : '';

        return "Hawk mac=\"$mac\", hash=\"$hash\"" . $extSet;
    }

    /**
     * Record used nonce and forget expired nonces.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to record.
     * @return void
     */
    private function manageNonces(string $nonce)
    {
        switch ($this->backend) {
            case 'file':
                $this->manageNonceFile($nonce);
                break;
            case 'database':
            case 'db':
                $this->manageNonceDatabase($nonce);
                break;
            case 'cache':
                $this->manageNonceCache($nonce);
                break;
            default:
                throw new Exception("Unknown Hawk backend: {$this->backend}");
        }
    }

    /**
     * Record used nonce and forget expired nonces in file.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to record.
     * @return void
     */
    private function manageNonceFile(string $nonce)
    {
        $timestamp = date('U');
        $file = $this->config->hawk->get('nonce_dir', '/tmp') . '/' .
            $this->nonceFile;
        if (file_exists($file)) {
            /**
             * Manage recorded nonces
             */
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $handle = fopen($file, 'w');
            foreach ($lines as $line) {
                $parts = explode(',', $line);
                if (sizeof($parts) === 2) {
                    if ($parts[1] + $this->config->hawk->get('expire', 60)
                            < $timestamp) {
                        /**
                         * Forget expired nonces
                         */
                        continue;
                    }
                    fwrite($handle, $line . "\n");
                } else {
                    throw new Exception("Malformed row in $file: $line");
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
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to record.
     * @return void
     */
    private function manageNonceDatabase(string $nonce)
    {
        $timestamp = date('U');
        if (!isset($this->config->hawk)
                || !isset($this->config->hawk->nonce_model)) {
            throw new Exception(
                "Nonce backend 'database' requires nonce_model set in hawk " .
                "section in config file."
            );
        }

        $modelName = $this->config->hawk->nonce_model;

        if (!class_exists($modelName)) {
            throw new Exception("Nonce model not found: " . $modelName);
        }

        $model = new $modelName;

        $this->modelsManager->executeQuery(
            "DELETE FROM $modelName WHERE (timestamp + :expire:) < :timestamp:",
            array(
                'expire' => $this->config->hawk->get('expire', 60),
                'timestamp' => $timestamp,
            )
        );

        $model->nonce = $nonce;
        $model->timestamp = $timestamp;
        if (!$model->save()) {
            throw new Exception("Could not save nonce to database");
        }
    }

    /**
     * Record used nonce in cache.
     *
     * @throws \Freischutz\Application\Exception
     * @return void
     */
    private function manageNonceCache()
    {
        if (!$this->di->has('cache')) {
            throw new Exception(
                "Nonce backend 'cache' requires cache service to be configured."
            );
        }
        $this->cache->save($this->nonceCacheKey, true);
    }

    /**
     * Check if nonce has been used previously.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to look up.
     * @return bool
     */
    private function lookUpNonce(string $nonce):bool
    {
        switch ($this->backend) {
            case 'file':
                $result = $this->lookUpNonceInFile($nonce);
                break;
            case 'database':
            case 'db':
                $result = $this->lookUpNonceInDatabase($nonce);
                break;
            case 'cache':
                $result = $this->lookUpNonceInCache();
                break;
            default:
                throw new Exception("Unknown nonce backend: {$this->backend}");
        }
        return $result;
    }

    /**
     * Check if nonce is recorded in file.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to look up.
     * @return bool
     */
    private function lookUpNonceInFile(string $nonce):bool
    {
        $file = $this->config->hawk->get('nonce_dir', '/tmp') . '/' .
            $this->nonceFile;
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
                throw new Exception("Malformed row in $file: $line");
            }
        }

        return false;
    }

    /**
     * Check if nonce is recorded in database.
     *
     * @throws \Freischutz\Application\Exception
     * @param string $nonce Nonce to look up.
     * @return bool
     */
    private function lookUpNonceInDatabase(string $nonce):bool
    {
        if (!isset($this->config->hawk)
                || !isset($this->config->hawk->nonce_model)) {
            throw new Exception(
                "Nonce backend 'database' requires nonce_model set in hawk " .
                "section in config file."
            );
        }

        $modelName = $this->config->hawk->nonce_model;

        if (!class_exists($modelName)) {
            throw new Exception("Nonce model not found: " . $modelName);
        }

        $model = new $modelName;
        $metadata = $model->getModelsMetaData();

        if (!$metadata->hasAttribute($model, 'nonce')
                || !$metadata->hasAttribute($model, 'timestamp')) {
            throw new Exception(
                "Nonce model must contain columns 'nonce' and 'timestamp'."
            );
        }
        $search = array("nonce=':nonce:'", array('nonce' => $nonce));
        return $model->findFirst($search) ? true : false;
    }

    /**
     * Check if nonce is recorded in cache.
     *
     * @throws \Freischutz\Application\Exception
     * @return bool
     */
    private function lookUpNonceInCache():bool
    {
        if (!$this->di->has('cache')) {
            throw new Exception(
                "Nonce backend 'cache' requires cache service to be configured."
            );
        }
        return $this->cache->get($this->nonceCacheKey);
    }
}
