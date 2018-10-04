<?php

use Freischutz\Utility\Jwt;
use Phalcon\Config\Adapter\Ini as Config;
use Phalcon\Mvc\Router;
use PHPUnit\Framework\TestCase;

class JwtTest extends TestCase
{
    private $configFile;
    private $configDb;

    public function setUp()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->configFile = new Config($appDir . '/../_shared/config/config.ini');
        $this->configFile->application->offsetSet('app_dir', $appDir);
        require $appDir . '/config/autoloader.php';

    }

    /**
     * Fake some request stuff.
     */
    public function fakeRequest($header)
    {
        // Fake some request stuff
        $_SERVER['HTTP_AUTHORIZATION'] = $header;
        $_SERVER['CONTENT_TYPE'] = 'text/plain';
        $_SERVER['REQUEST_URI'] = '/hello';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = 80;
    }

    /**
     * Test correct request passes validation.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testOk()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertSame('Hello world!', $out);
    }

    /**
     * Test wrong key denied.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testWrongKey()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'wrong');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Signature invalid.', $out);
    }

    /**
     * Test invalid user given.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testMissingUserCredentials()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'wrong',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('User does not exist.', $out);
    }

    /**
     * Test no user given.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testNoUserGiven()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => '',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token subject empty.', $out);
    }

    /**
     * Test wrong audience.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testWrongAudience()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'iss' => 'freischutz',
            'aud' => '',
            'exp' => $now + 360,
            'iat' => $now,
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token audience mismatch.', $out);
    }

    /**
     * Test wrong issuer.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testWrongIssuer()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'iss' => '',
            'aud' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token issuer mismatch.', $out);
    }

    /**
     * Test missing claims.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testMissingClaims()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz'
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertRegExp('/^Missing claims: [a-z]+, [a-z]+.$/i', $out);
    }

    /**
     * Test expired.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testExpired()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now - 1,
            'iat' => $now,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token expired.', $out);
    }

    /**
     * Test issued at.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testFuture()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now + 360,
            'nbf' => $now
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token issued at a future time.', $out);
    }

    /**
     * Test not before.
     * @preserveGlobalState disabled
     * @runInSeparateProcess
     */
    public function testNotBefore()
    {
        $now = time();
        $header = (object) array(
            'typ' => 'jwt',
            'alg' => 'HS256'
        );
        $payload = (object) array(
            'sub' => 'user',
            'aud' => 'freischutz',
            'iss' => 'freischutz',
            'exp' => $now + 360,
            'iat' => $now,
            'nbf' => $now + 10
        );
        $jwt = Jwt::create($header, $payload, 'pw');

        $this->fakeRequest("Bearer $jwt");

        // Set up application
        $app = new Freischutz\Application\Core($this->configFile);
        // Force reading URI from $_SERVER['REQUEST_URI']
        $app->router->setUriSource(Router::URI_SOURCE_SERVER_REQUEST_URI);

        // Catch output
        ob_start();
        $result = $app->run();
        $out = ob_get_contents();
        ob_end_clean();
        ob_get_clean();

        $this->assertSame('Token not yet valid.', $out);
    }
}
