Public API Documentation
========================

Table of contents
-----------------

- [\Freischutz\Application\Exception](#class-freischutzapplicationexception)
- [\Freischutz\Application\Users](#class-freischutzapplicationusers)
- [\Freischutz\Application\Acl](#class-freischutzapplicationacl)
- [\Freischutz\Application\Router](#class-freischutzapplicationrouter)
- [\Freischutz\Application\Core](#class-freischutzapplicationcore)
- [\Freischutz\Application\Data](#class-freischutzapplicationdata)
- [\Freischutz\Security\Basic](#class-freischutzsecuritybasic)
- [\Freischutz\Security\Jwt](#class-freischutzsecurityjwt)
- [\Freischutz\Security\Hawk](#class-freischutzsecurityhawk)
- [\Freischutz\Utility\Jwt](#class-freischutzutilityjwt)
- [\Freischutz\Utility\Stopwatch](#class-freischutzutilitystopwatch)
- [\Freischutz\Utility\Base64url](#class-freischutzutilitybase64url)
- [\Freischutz\Utility\Response](#class-freischutzutilityresponse)
- [\Freischutz\Validation\Json](#class-freischutzvalidationjson)

<hr />

### Class: \Freischutz\Application\Exception

> Freischutz exception.

| Visibility | Function |
|:-----------|:---------|

*This class extends \Exception*

*This class implements \Throwable*

<hr />

### Class: \Freischutz\Application\Users

> Freischutz user handling component.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |
| public | <strong>getUser()</strong> : <em>[\stdClass](http://php.net/manual/en/class.stdclass.php)</em><br /><em>Get user.</em> |
| public | <strong>setUser(</strong><em>\string</em> <strong>$id</strong>)</strong> : <em>bool</em><br /><em>Set user. User object matching given string $id is read from $userList property and written to $user property.</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Application\Acl

> Freischutz Access Control List component.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>isAllowed(</strong><em>\string</em> <strong>$role</strong>, <em>\string</em> <strong>$controller</strong>, <em>\string</em> <strong>$action</strong>)</strong> : <em>bool</em><br /><em>Check if role is allowed to access resource.</em> |
| public | <strong>rebuild(</strong><em>\boolean</em> <strong>$useCache=false</strong>)</strong> : <em>void</em><br /><em>Rebuild ACL.</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Application\Router

> Freischutz router component.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Application\Core

> Freischutz application core.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>[\Phalcon\Config\Adapter\Ini](http://php.net/manual/en/class.phalconconfigadapterini.php)</em> <strong>$config</strong>)</strong> : <em>void</em> |
| public static | <strong>getVersion()</strong> : <em>string</em><br /><em>Get Freischutz version.</em> |
| public | <strong>run()</strong> : <em>void</em><br /><em>Run application and display output.</em> |

*This class extends \Phalcon\Mvc\Application*

*This class implements \Phalcon\Events\EventsAwareInterface, \Phalcon\Di\InjectionAwareInterface*

<hr />

### Class: \Freischutz\Application\Data

> Freischutz data handling component.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct(</strong><em>mixed</em> <strong>$data</strong>)</strong> : <em>void</em> |
| public | <strong>get()</strong> : <em>mixed</em><br /><em>Get data handled according to content-type.</em> |
| public | <strong>getJson(</strong><em>bool</em> <strong>$assoc=false</strong>)</strong> : <em>[\stdClass](http://php.net/manual/en/class.stdclass.php)/string[]/int[]/false</em><br /><em>Handle JSON data.</em> |
| public | <strong>getRaw()</strong> : <em>mixed</em><br /><em>Get raw data.</em> |
| public | <strong>getXml()</strong> : <em>\SimpleXMLElement/false</em><br /><em>Handle XML data.</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Security\Basic

> Basic authentication.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Security\Jwt

> JSON Web Token authentication.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Security\Hawk

> Hawk authentication. Implementation of the Hawk protocol. HTTP HMAC authentication with partial cryptographic verification of request, which covers method, URI, host and port, various other authentication details, and payload. Optionally allows for verification of response.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |

*This class extends \Phalcon\Mvc\User\Component*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Events\EventsAwareInterface*

<hr />

### Class: \Freischutz\Utility\Jwt

> JSON Web Token functions.

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>create(</strong><em>[\stdClass](http://php.net/manual/en/class.stdclass.php)</em> <strong>$header</strong>, <em>[\stdClass](http://php.net/manual/en/class.stdclass.php)</em> <strong>$payload</strong>, <em>\string</em> <strong>$secret</strong>)</strong> : <em>string/false</em><br /><em>Create JSON Web Token.</em> |
| public static | <strong>createSignature(</strong><em>\string</em> <strong>$algorithm</strong>, <em>\string</em> <strong>$token</strong>, <em>string</em> <strong>$secret</strong>)</strong> : <em>string</em><br /><em>Create signature from token.</em> |
| public static | <strong>validate(</strong><em>\string</em> <strong>$token</strong>, <em>\string</em> <strong>$secret</strong>)</strong> : <em>bool</em><br /><em>Validate token signature.</em> |

<hr />

### Class: \Freischutz\Utility\Stopwatch

> Stopwatch utility for timekeeping.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>__construct()</strong> : <em>void</em> |
| public | <strong>elapsed()</strong> : <em>float</em><br /><em>Get time elapsed without stopping.</em> |
| public | <strong>getMarks()</strong> : <em>float[]</em><br /><em>Get marks.</em> |
| public | <strong>mark()</strong> : <em>float</em><br /><em>Mark time elapsed without stopping.</em> |
| public | <strong>reset()</strong> : <em>void</em><br /><em>Reset start time to now, clear marks and clear stop time.</em> |
| public | <strong>stop()</strong> : <em>float</em><br /><em>Stop timekeeping.</em> |

<hr />

### Class: \Freischutz\Utility\Base64url

> Base64url functions.

| Visibility | Function |
|:-----------|:---------|
| public static | <strong>decode(</strong><em>\string</em> <strong>$input</strong>)</strong> : <em>string</em><br /><em>Decode from base64url.</em> |
| public static | <strong>encode(</strong><em>\string</em> <strong>$input</strong>)</strong> : <em>string</em><br /><em>Encode to base64url.</em> |

<hr />

### Class: \Freischutz\Utility\Response

> HTTP response utility.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>accepted(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>202 Accepted.</em> |
| public | <strong>badGateway(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>502 Bad Gateway.</em> |
| public | <strong>badRequest(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>400 Bad Request.</em> |
| public | <strong>conflict(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>409 Conflict.</em> |
| public | <strong>cont(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>100 Continue.</em> |
| public | <strong>created(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>201 Created.</em> |
| public | <strong>expectationFailed(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>417 Expectation Failed.</em> |
| public | <strong>failedDependency(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>424 Failed Dependency.</em> |
| public | <strong>forbidden(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>403 Forbidden.</em> |
| public | <strong>found(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>302 Found.</em> |
| public | <strong>gatewayTimeout(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>504 Gateway Timeout.</em> |
| public | <strong>getContentType()</strong> : <em>string</em><br /><em>Get response content type.</em> |
| public | <strong>gone(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>410 Gone.</em> |
| public | <strong>httpVersionNotSupported(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>505 HTTP Version Not Supported.</em> |
| public | <strong>imUsed(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>226 IM Used.</em> |
| public | <strong>insufficientStorage(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>507 Insufficient Storage.</em> |
| public | <strong>internalServerError(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>500 Internal Server Error.</em> |
| public | <strong>lengthRequired(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>411 Length Required.</em> |
| public | <strong>locked(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>423 Locked.</em> |
| public | <strong>loopDetected(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>508 Loop Detected.</em> |
| public | <strong>methodNotAllowed(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$methods</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>405 Method Not Allowed.</em> |
| public | <strong>misdirectedRequest(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>421 Misdirected Request.</em> |
| public | <strong>movedPermanently(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>301 Moved Permanently.</em> |
| public | <strong>multiStatus(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>207 Multi-Status.</em> |
| public | <strong>multipleChoices(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>300 Multiple Choices.</em> |
| public | <strong>networkAuthenticationRequired(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>511 Network Authentication Required.</em> |
| public | <strong>networkConnectTimeoutError(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>599 Network Connect Timeout Error.</em> |
| public | <strong>nginxClientClosedRequest()</strong> : <em>void</em><br /><em>499 Nginx: Client Closed Request.</em> |
| public | <strong>nginxConnectionClosedWithoutResponse()</strong> : <em>void</em><br /><em>444 Nginx: Connection Closed Without Response.</em> |
| public | <strong>noContent()</strong> : <em>void</em><br /><em>204 No Content.</em> |
| public | <strong>nonAuthoritativeInformation(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>203 Non-authoritative Information.</em> |
| public | <strong>notAcceptable(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>406 Not Acceptable.</em> |
| public | <strong>notExtended(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>510 Not Extended.</em> |
| public | <strong>notFound(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>404 Not Found.</em> |
| public | <strong>notImplemented(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>501 Not Implemented.</em> |
| public | <strong>notModified(</strong><em>mixed</em> <strong>$content</strong>, <em>array</em> <strong>$headers</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>304 Not Modified. The server generating a 304 response MUST generate any of the following header fields that would have been sent in a 200 OK response to the same request: Cache-Control, Content-Location, Date, ETag, Expires, and Vary.</em> |
| public | <strong>ok(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>200 OK.</em> |
| public | <strong>partialContent(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$range</strong>, <em>string</em> <strong>$rangeUnit</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>206 Partial Content.</em> |
| public | <strong>payloadTooLarge(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$retry=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>413 Payload Too Large. situation is temporary.</em> |
| public | <strong>permanentRedirect(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>308 Permanent Redirect.</em> |
| public | <strong>preconditionFailed(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>412 Precondition Failed.</em> |
| public | <strong>preconditionRequired(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>428 Precondition Required.</em> |
| public | <strong>processing(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>102 Processing.</em> |
| public | <strong>proxyAuthenticationRequired(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$authenticate</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>407 Proxy Authentication Required.</em> |
| public | <strong>requestHeaderFieldsTooLarge(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>431 Request Header Fields Too Large.</em> |
| public | <strong>requestTimeout(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>408 Request Timeout.</em> |
| public | <strong>requestUriTooLong(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>414 Request-URI Too Long.</em> |
| public | <strong>requestedRangeNotSatisfiable(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$range=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>416 Requested Range Not Satisfiable</em> |
| public | <strong>resetContent()</strong> : <em>void</em><br /><em>205 Reset Content.</em> |
| public | <strong>seeOther(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>303 See Other.</em> |
| public | <strong>serviceUnavailable(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$retry=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>503 Service Unavailable.</em> |
| public | <strong>switchingProtocols(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$protocol</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>101 Switching Protocols.</em> |
| public | <strong>temporaryRedirect(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$location=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>307 Temporary Redirect.</em> |
| public | <strong>tooManyRequests(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$retry=false</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>429 Too Many Requests.</em> |
| public | <strong>unauthorized(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$authenticate</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>401 Unauthorized.</em> |
| public | <strong>unavailableForLegalReasons(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>451 Unavailable For Legal Reasons.</em> |
| public | <strong>unprocessableEntity(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>422 Unprocessable Entity.</em> |
| public | <strong>unsupportedMediaType(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>415 Unsupported Media Type.</em> |
| public | <strong>upgradeRequired(</strong><em>mixed</em> <strong>$content</strong>, <em>string</em> <strong>$protocol</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>426 Upgrade Required.</em> |
| public | <strong>variantAlsoNegotiates(</strong><em>mixed</em> <strong>$content</strong>, <em>bool/string/false</em> <strong>$type=false</strong>, <em>bool/string/false</em> <strong>$charset=false</strong>)</strong> : <em>void</em><br /><em>506 Variant Also Negotiates.</em> |

*This class extends \Phalcon\Http\Response*

*This class implements \Phalcon\Di\InjectionAwareInterface, \Phalcon\Http\ResponseInterface*

<hr />

### Class: \Freischutz\Validation\Json

> JSON attribute validation.

| Visibility | Function |
|:-----------|:---------|
| public | <strong>validate(</strong><em>[\Phalcon\Validation](http://php.net/manual/en/class.phalconvalidation.php)</em> <strong>$validation</strong>, <em>mixed</em> <strong>$attribute</strong>)</strong> : <em>bool</em><br /><em>Execute validation.</em> |

*This class extends \Phalcon\Validation\Validator*

*This class implements \Phalcon\Validation\ValidatorInterface*

