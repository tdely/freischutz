<?php
namespace Freischutz\Utility;

use Phalcon\Http\Response as PhalconResponse;

/**
 * Response utility
 */
class Response extends PhalconResponse
{

    /**
     * Set content and automatically select content type.
     *
     * When $type is not set, content type will be determined by the data type
     * of $content and if an object, the object class. Default content type
     * is 'text/plain', when no other type matches.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return void
     */
    private function setContentAuto($content, $type = false, $charset = false)
    {
        $charset = $charset !== false ? $charset : 'UTF-8';

        /**
         * Select content type intelligently if $type is not set
         */
        $varType = gettype($content);
        switch (true) {
            case ($type && $type !== 'application/xml' && $type !== 'application/json'):
                // Use unknown given type
                break;
            case ($type === 'application/xml'):
            case ($varType === 'object' && get_class($content) === 'SimpleXMLElement'):
                $content = $content->asXML();
                $type = 'application/xml';
                break;
            case ($type === 'application/json'):
            case ($varType === 'object' || $varType === 'array'):
                $content = json_encode($content);
                $type = 'application/json';
                break;
            default:
                $type = 'text/plain';
                break;
        }

        $this->setContentType($type, $charset);
        $this->setContent($content);
    }

    /**
     * Informational
     */

    /**
     * 100 Continue.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function cont($content, $type = false, $charset = false)
    {
        $this->setStatusCode(100);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 101 Switching Protocols.
     *
     * @param mixed $content Response content.
     * @param string $protocol New protocol.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function switchingProtocols($content, $protocol, $type = false, $charset = false)
    {
        $this->setStatusCode(101);
        $this->setHeader('Upgrade', $protocol);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 102 Processing.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function processing($content, $type = false, $charset = false)
    {
        $this->setStatusCode(102);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * Success
     */

    /**
     * 200 OK.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function ok($content, $type = false, $charset = false)
    {
        $this->setStatusCode(200);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 201 Created.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for created resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function created($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(201);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 202 Accepted.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function accepted($content, $type = false, $charset = false)
    {
        $this->setStatusCode(202);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 203 Non-authoritative Information.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function nonAuthoritativeInformation($content, $type = false, $charset = false)
    {
        $this->setStatusCode(203);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 204 No Content.
     *
     * @return \Phalcon\Http\Response
     */
    public function noContent()
    {
        $this->setStatusCode(204);
        $this->setContentLength(0);
    }

    /**
     * 205 Reset Content.
     *
     * @return \Phalcon\Http\Response
     */
    public function resetContent()
    {
        $this->setStatusCode(205);
        $this->setContentLength(0);
    }

    /**
     * 206 Partial Content.
     *
     * @param mixed $content Response content.
     * @param string $range Content-Range header field numbers.
     * @param string $rangeUnit Content-Range header field unit.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function partialContent($content, $range, $rangeUnit, $type = false, $charset = false)
    {
        $this->setHeader('Content-Range', "$rangeUnit $range");
        $this->setStatusCode(206);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 207 Multi-Status.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function multiStatus($content, $type = false, $charset = false)
    {
        $this->setStatusCode(207);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 226 IM Used.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function imUsed($content, $type = false, $charset = false)
    {
        $this->setStatusCode(226);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * Redirection
     */

    /**
     * 300 Multiple Choices.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) Preferred URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function multipleChoices($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(300);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 301 Moved Permanently.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function movedPermanently($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(301);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 302 Found.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function found($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(302);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 303 See Other.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function seeOther($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(303);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 304 Not Modified.
     *
     * The server generating a 304 response MUST generate any of the following
     * header fields that would have been sent in a 200 OK response to the
     * same request: Cache-Control, Content-Location, Date, ETag, Expires,
     * and Vary.
     *
     * @param mixed $content Response content.
     * @param array $headers Associative array with header fields to set.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function notModified($content, $headers, $type = false, $charset = false)
    {
        $setable = array('Cache-Control', 'Content-Location', 'Date', 'ETag', 'Expires', 'Vary');
        $this->setStatusCode(304);
        foreach ($headers as $header => $value) {
            if (in_array($header, $setable)) {
                $this->setHeader($header, $value);
            }
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 307 Temporary Redirect.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function temporaryRedirect($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(307);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 308 Permanent Redirect.
     *
     * @param mixed $content Response content.
     * @param string $location (optional) URI locator for resource.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function permanentRedirect($content, $location = false, $type = false, $charset = false)
    {
        $this->setStatusCode(308);
        if ($location) {
            $this->setHeader('Location', $location);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * Client Error
     */

    /**
     * 400 Bad Request.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function badRequest($content, $type = false, $charset = false)
    {
        $this->setStatusCode(400);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 401 Unauthorized.
     *
     * @param mixed $content Response content.
     * @param string $authenticate Authentication challenge hint.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function unauthorized($content, $authenticate, $type = false, $charset = false)
    {
        $this->setStatusCode(401);
        $this->setHeader('WWW-Authenticate', $authenticate);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 403 Forbidden.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function forbidden($content, $type = false, $charset = false)
    {
        $this->setStatusCode(403);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 404 Not Found.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function notFound($content, $type = false, $charset = false)
    {
        $this->setStatusCode(404);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 405 Method Not Allowed.
     *
     * @param mixed $content Response content.
     * @param string $methods Supported methods.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function methodNotAllowed($content, $methods, $type = false, $charset = false)
    {
        $this->setStatusCode(405);
        $this->setHeader('Allow', $methods);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 406 Not Acceptable.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function notAcceptable($content, $type = false, $charset = false)
    {
        $this->setStatusCode(406);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 407 Proxy Authentication Required.
     *
     * @param mixed $content Response content.
     * @param string $authenticate Authentication challenge hint.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function proxyAuthenticationRequired($content, $authenticate, $type = false, $charset = false)
    {
        $this->setStatusCode(407);
        $this->setHeader('Proxy-Authenticate', $authenticate);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 408 Request Timeout.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function requestTimeout($content, $type = false, $charset = false)
    {
        $this->setStatusCode(408);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 409 Conflict.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function conflict($content, $type = false, $charset = false)
    {
        $this->setStatusCode(409);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 410 Gone.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function gone($content, $type = false, $charset = false)
    {
        $this->setStatusCode(410);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 411 Length Required.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function lengthRequired($content, $type = false, $charset = false)
    {
        $this->setStatusCode(411);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 412 Precondition Failed.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function preconditionFailed($content, $type = false, $charset = false)
    {
        $this->setStatusCode(412);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 413 Payload Too Large.
     *
     * @param mixed $content Response content.
     * @param string $retry (optional) Seconds or timestamp after to retry if
     *   situation is temporary.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function payloadTooLarge($content, $retry = false, $type = false, $charset = false)
    {
        $this->setStatusCode(413);
        if ($retry) {
            $this->setHeader('Retry-After', $retry);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 414 Request-URI Too Long.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function requestUriTooLong($content, $type = false, $charset = false)
    {
        $this->setStatusCode(414);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 415 Unsupported Media Type.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function unsupportedMediaType($content, $type = false, $charset = false)
    {
        $this->setStatusCode(415);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 416 Requested Range Not Satisfiable
     *
     * @param mixed $content Response content.
     * @param string $range (optional) Length of the selected representation.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function requestedRangeNotSatisfiable($content, $range = false, $type = false, $charset = false)
    {
        $this->setStatusCode(416);
        if ($range) {
            $this->setHeader('Content-Range', $range);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 417 Expectation Failed.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function expectationFailed($content, $type = false, $charset = false)
    {
        $this->setStatusCode(417);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 421 Misdirected Request.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function misdirectedRequest($content, $type = false, $charset = false)
    {
        $this->setStatusCode(421);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 422 Unprocessable Entity.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function unprocessableEntity($content, $type = false, $charset = false)
    {
        $this->setStatusCode(422);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 423 Locked.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function locked($content, $type = false, $charset = false)
    {
        $this->setStatusCode(423);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 424 Failed Dependency.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function failedDependency($content, $type = false, $charset = false)
    {
        $this->setStatusCode(424);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 426 Upgrade Required.
     *
     * @param mixed $content Response content.
     * @param string $protocol New protocol.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function upgradeRequired($content, $protocol, $type = false, $charset = false)
    {
        $this->setStatusCode(426);
        $this->setHeader('Upgrade', $protocol);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 428 Precondition Required.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function preconditionRequired($content, $type = false, $charset = false)
    {
        $this->setStatusCode(428);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 429 Too Many Requests.
     *
     * @param mixed $content Response content.
     * @param string $retry (optional) Seconds or timestamp after to retry.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function tooManyRequests($content, $retry = false, $type = false, $charset = false)
    {
        $this->setStatusCode(429);
        if ($retry) {
            $this->setHeader('Retry-After', $retry);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 431 Request Header Fields Too Large.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function requestHeaderFieldsTooLarge($content, $type = false, $charset = false)
    {
        $this->setStatusCode(431);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 444 Nginx: Connection Closed Without Response.
     *
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function nginxConnectionClosedWithoutResponse()
    {
        $this->setStatusCode(444);
    }

    /**
     * 451 Unavailable For Legal Reasons.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function unavailableForLegalReasons($content, $type = false, $charset = false)
    {
        $this->setStatusCode(451);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 499 Nginx: Client Closed Request.
     *
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function nginxClientClosedRequest()
    {
        $this->setStatusCode(499);
    }

    /**
     * Server Error
     */

    /**
     * 500 Internal Server Error.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function internalServerError($content, $type = false, $charset = false)
    {
        $this->setStatusCode(500);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 501 Not Implemented.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function notImplemented($content, $type = false, $charset = false)
    {
        $this->setStatusCode(501);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 502 Bad Gateway.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function badGateway($content, $type = false, $charset = false)
    {
        $this->setStatusCode(502);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 503 Service Unavailable.
     *
     * @param mixed $content Response content.
     * @param string $retry (optional) Seconds or timestamp after to retry.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function serviceUnavailable($content, $retry = false, $type = false, $charset = false)
    {
        $this->setStatusCode(503);
        if ($retry) {
            $this->setHeader('Retry-After', $retry);
        }
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 504 Gateway Timeout.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function gatewayTimeout($content, $type = false, $charset = false)
    {
        $this->setStatusCode(504);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 505 HTTP Version Not Supported.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function httpVersionNotSupported($content, $type = false, $charset = false)
    {
        $this->setStatusCode(505);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 506 Variant Also Negotiates.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function variantAlsoNegotiates($content, $type = false, $charset = false)
    {
        $this->setStatusCode(506);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 507 Insufficient Storage.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function insufficientStorage($content, $type = false, $charset = false)
    {
        $this->setStatusCode(507);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 508 Loop Detected.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function loopDetected($content, $type = false, $charset = false)
    {
        $this->setStatusCode(508);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 510 Not Extended.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function notExtended($content, $type = false, $charset = false)
    {
        $this->setStatusCode(510);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 511 Network Authentication Required.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function networkAuthenticationRequired($content, $type = false, $charset = false)
    {
        $this->setStatusCode(511);
        $this->setContentAuto($content, $type, $charset);
    }

    /**
     * 599 Network Connect Timeout Error.
     *
     * @param mixed $content Response content.
     * @param string $type (optional) Explicitly set content type.
     * @param string $charset (optional) Override default charset.
     * @return \Phalcon\Http\Response
     */
    public function networkConnectTimeoutError($content, $type = false, $charset = false)
    {
        $this->setStatusCode(599);
        $this->setContentAuto($content, $type, $charset);
    }
}
