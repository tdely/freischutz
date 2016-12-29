<?php

use Phalcon\Config\Adapter\Ini as Config;
use PHPUnit\Framework\TestCase;

class UsersTest extends TestCase
{
    private $config;

    public function __construct()
    {
        $appDir = __DIR__ . '/_shared';
        $libDir = __DIR__ . '/../../lib';
        $this->config = new Config($appDir . '/../_config/config_users.ini');
        $this->config->application->offsetSet('app_dir', $appDir);
        require $appDir . '/config/autoloader.php';
    }

    public function testUserExists()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->config);

        // Act
        $userExists = $app->users->setUser('user');

        // Assert
        $this->assertTrue($userExists);
    }

    public function testUserMissing()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->config);

        // Act
        $userExists = $app->users->setUser('resu');

        // Assert
        $this->assertFalse($userExists);
    }

    public function testGetUser()
    {
        // Arrange
        $app = new Freischutz\Application\Core($this->config);

        // Act
        $app->users->setUser('user');
        $user = $app->users->getUser();

        // Assert
        $this->assertEquals(gettype($user), 'object');
        $this->assertObjectHasAttribute('id', $user);
        $this->assertObjectHasAttribute('key', $user);
        $this->assertEquals('user', $user->id);
        $this->assertEquals('pw', $user->key);
    }
}
