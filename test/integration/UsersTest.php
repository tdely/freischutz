<?php

use Phalcon\Config\Adapter\Ini as Config;
use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase
{
    private $config;
    private $configFile;

    public function __construct()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->config = new Config($appDir . '/../_config/config_users_config.ini');
        $this->config->application->offsetSet('app_dir', $appDir);
        $this->configFile = new Config($appDir . '/../_config/config_users_file.ini');
        $this->configFile->application->offsetSet('app_dir', $appDir);
        require $appDir . '/config/autoloader.php';
    }

    /**
     * Test that user gets set correctly and contains 'id' and 'key'.
     */
    public function testUserConfigOk()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->config);

        // Act
        $userExists = $app->users->setUser('user');
        $user = $app->users->getUser();

        // Assert
        $this->assertTrue($userExists);
        $this->assertEquals(gettype($user), 'object');
        $this->assertObjectHasAttribute('id', $user);
        $this->assertObjectHasAttribute('key', $user);
        $this->assertEquals('user', $user->id);
        $this->assertEquals('pw', $user->key);
    }

    /**
     * Test that user gets set correctly and contains 'id' and 'key'.
     */
    public function testUserFileOk()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->configFile);

        // Act
        $userExists = $app->users->setUser('user');
        $user = $app->users->getUser();

        // Assert
        $this->assertTrue($userExists);
        $this->assertEquals(gettype($user), 'object');
        $this->assertObjectHasAttribute('id', $user);
        $this->assertObjectHasAttribute('key', $user);
        $this->assertEquals('user', $user->id);
        $this->assertEquals('pw', $user->key);
    }

    /**
     * Test that setting unknown user returns false.
     */
    public function testUserMissing()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->config);

        // Act
        $userExists = $app->users->setUser('resu');

        // Assert
        $this->assertFalse($userExists);
    }
}
