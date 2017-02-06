## Getting Started

Let's take a look at working with the framework through the reference
implementation available in the example directory

These are the files we'll be looking at:
<pre>
<strong>example/reference/</strong>
+--<strong>private/</strong>
|  +--<strong>config/</strong>
|  |  +--<strong>acl/</strong>
|  |  |  +--Example.resources
|  |  |  +--Example.roles
|  |  |  `--Example.rules
|  |  +--<strong>databases/</strong>
|  |  |  `--default.php
|  |  +--<strong>routes/</strong>
|  |  |  `--Example.routes
|  |  +--<strong>users/</strong>
|  |  |  `--Example.users
|  |  +--autoloader.php
|  |  `--config.ini
|  +--<strong>controllers/</strong>
|  |  +--NotFoundController.php
|  |  `--ExampleController.php
|  `--<strong>models/</strong>
|     `--Example.php
`--<strong>public/</strong>
   `--index.php
</pre>

### index.php

The index file is the only file which should be accessible through the web server.

To put your application in a different location relative to the lib directory,
change the LIB_DIR definition `define('LIB_DIR', __DIR__ . '/../../../lib');`
to point correctly. Since we are using a file structure where the application
directory is parallel to the directory containing index.php, the APP_DIR
constant is defined as `__DIR__ . '/../private'`.


Change this line to log under a different name:
`openlog('freischutz_example', LOG_CONS | LOG_NDELAY | LOG_PID, LOG_USER);`

`example/reference/public/index.php`:
```php
<?php

use Phalcon\Config\Adapter\Ini as Config;

// Define important directories
define('APP_DIR', __DIR__ . '/../private');
define('LIB_DIR', __DIR__ . '/../../../lib');

// Set up syslog
openlog('freischutz_test', LOG_CONS | LOG_NDELAY | LOG_PID, LOG_USER);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error['type'] === E_ERROR) {
        syslog(LOG_ERR,
            'Error: ' . $error['message'] . ' in ' . $error['file'] .
            ' on line ' . $error['line']
        );
        http_response_code(500);
        echo "An unexpected problem occurred.";
    }
    closelog();
});

try {
    // Load config
    $config = new Config(APP_DIR . '/config/config.ini');

    // Add application directory path to config
    $config->application->offsetSet('app_dir', APP_DIR);

    // Load auto-loader
    require APP_DIR . '/config/autoloader.php';

    // Set up application
    $app = new Freischutz\Application\Core($config);

    // Execute
    $app->run();
} catch (\Exception $e) {
    // Log errors
    syslog(LOG_ERR,
        'Fatal exception: ' . $e->getMessage() . ' in ' . $e->getFile() .
        ' on line ' . $e->getLine() . "\nStack trace:\n" .
        $e->getTraceAsString()
    );
    closelog();

    // Respond with a generic error message
    http_response_code(500);
    echo 'An unexpected problem occurred.';
}
```


### Autoloader

All classes are included by autoloader, which in the case of Phalcon means that
we register our namespaces together with the directory they reside in. In the
reference implementation the models and controllers are in the 'Reference'
namespace, the path for these are based on the APP_DIR constant defined in
index.php.

`example/reference/private/config/autoloader.php`:
```php
<?php

use Phalcon\Loader as PhalconLoader;

$loader = new PhalconLoader();

$loader->registerNamespaces(array(
    'Freischutz' => LIB_DIR,
    'Freischutz\Application' => LIB_DIR . '/application',
    'Freischutz\Security' => LIB_DIR . '/security',
    'Freischutz\Utility' => LIB_DIR . '/utility',
    'Reference\Controllers' => APP_DIR . '/controllers',
    'Reference\Models' => APP_DIR . '/models',
));

$loader->register();
```


### Configuration

```
[application]
base_uri             = /reference
strict_host_check    = true
routes_dir           = /config/routes
databases_dir        = /config/databases
controller_namespace = Reference\Controllers
users_backend        = file
users_dir            = /config/users
cache_adapter        = false
cache_lifetime       = 60
authenticate         = Hawk
metadata_adapter     = Memory

[hawk]
algorithms  = sha256,sha512
expire      = 60
backend     = file
disclose    = true
nonce_dir   = /tmp

[acl]
enable         = true
backend        = file
dir            = /config/acl
```


### Database

Multiple database connections may be set up, but one default connection must be
created as 'default.php' which becomes available through the Phalcon dependency
injector (DI) as 'db'. Models will automatically use the default database unless
another is explicitly set in the model class (see the Model section further
down).

All files in the databases directory are automatically included as database
connections, and become available through the DI. If a database file is named
'Example.php' it becomes available as 'dbExample'.

`example\reference\private\config\databases/default.php`
```php
<?php

$database = [
    'adapter' => 'mysql',
    'host' => '127.0.0.1',
    'port' => 3306,
    'username' => 'root',
    'password' => '',
    'dbname' => 'freischutz_example',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_EMULATE_PREPARES => false,         // Use native prepared statements
        PDO::ATTR_STRINGIFY_FETCHES => false,        // Keep data types from database
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exception on PDO error
    ],
];

return $database;
```


### Controllers

`example/reference/private/controllers/NotFoundController.php`:
```php
<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;

/**
 * Handle requests to unknown routes.
 */
class NotFoundController extends Controller
{
    /**
     * Respond with 404 Not Found
     */
    public function notFoundAction()
    {
        $response = new Response();
        $response->notFound('Resource not found.');
        return $response;
    }
}

```

`example/reference/private/controllers/ExampleController.php`:
```php
<?php
namespace Reference\Controllers;

use Freischutz\Utility\Response;
use Phalcon\Mvc\Controller;
use Reference\Models\Example;

/**
 * Controller illustrating some basic functionality, also usable for making
 * requests while testing configuration.
 */
class ExampleController extends Controller
{
    /**
     * Respond with 'Hello world!'
     */
    public function helloAction()
    {
        $response = new Response();
        $response->ok('Hello world!');
        return $response;
    }

    /**
     * XML example.
     *
     * Takes POST payload and attempts to turn it into a SimpleXMLElement
     * object. If successful responds with 200 OK, sending the re-serialized
     * object back as response payload. If failing to convert data into a
     * SimpleXMLElement it will respond with 400 Bad Request.
     */
    public function xmlAction()
    {
        $response = new Response();
        $data = $this->data->getXml();
        if ($data) {
            $response->ok($data);
        } else {
            $response->badRequest('Data malformed or missing');
        }
        return $response;
    }

    /**
     * JSON example.
     *
     * Takes POST payload and attempts to decode it into a standard object
     * If successful responds with 200 OK, sending the re-serialized
     * object back as response payload. If json_decode fails to convert data
     * into an object it will respond with 400 Bad Request.
     */
    public function jsonAction()
    {
        $response = new Response();
        $data = $this->data->getJson();
        if ($data) {
            $response->ok($data);
        } else {
            $response->badRequest('Data malformed or missing');
        }
        return $response;
    }

    /**
     * Database example.
     *
     * Retrieves all Example model records, which is all rows from the models
     * source table (example).
     */
    public function getAction()
    {
        $response = new Response();
        // Get Phalcon resultset, each row is built when it becomes required
        $result = Example::find();
        // Get all objects from resultset (all rows become built)
        $data = $result->toArray();
        $response->ok($data);
        return $response;
    }
}
```


### Models

Phalcon models automatically map to a database table, the model class name is
converted from CamelCase to snake_case: 'Example' becomes 'example', 'MyTest'
becomes 'my_test'.

`example/reference/private/models/Example.php`:
```php
<?php
namespace Reference\Models;

use Phalcon\Mvc\Model;

/**
 * Model for example table
 */
class Example extends Model
{

}
```

Using a non-default connection service may be accomplished by adding something
like this:
```php
public function initialize()
{
    $this->setConnectionService('dbExample');
}
```

### Routes

Routes are rules for matching a HTTP request to a controller action depending on
which URI and HTTP method was used.

Route files end in '.routes' and are made up of 4 CSV fields:

* Controller name
* Action name
* Pattern: Regex for URI matching
* Method: HTTP method

`example/reference/private/config/routes/Example.routes`:
```
# Controller,Action,Pattern,Method
test,hello,/hello,get
test,get,/test,get
test,xml,/xml,post
test,json,/json,post
```


### Users

To use authentication and ACL, users need to exists with which to identify client requests.
When using ACL the user identifier must have a corresponding ACL role (ID must match a role name).

User files end in '.users' and are made up of 2 CSV fields:

* ID: an identifier for the user
* Key: a secret key string for authentication

`example/reference/private/config/users/Example.users`:
```
# ID,Key
jane,asd123
```


### ACL

The Access Control List is built from three components: resources, roles, and
rules. Resources are controller actions, roles are 'users' and/or 'groups', and
rules determine whether to allow or deny access for one role to a resource.

#### Resources

Controllers and their actions.

Resource files end in '.resources' and are made up of 3 CSV fields:

* Controller name
* Description (may be left blank)
* Actions: a nested CSV string delimited by ';' (semicolon), each of these being a separate controller action

`example/reference/private/config/acl/Example.resources`:
```
# Controller,Description,Action1;Action2;ActionN
not-found,Resource missing route,notFound
example,Example resource,json;xml;get;hello
```


#### Roles

Roles representing a user or group.
For a user role the role name must correspond to a user ID.

Role files end in '.roles' and are made up of 2 CSV fields:

* Role name
* Description (may be left blank)

`example/reference/private/config/acl/Example.roles`:
```
# Role,Description
user,Example group
jane,Example user
```

A secondary type of role files are inheritance files, used to apply rules from
one role unto another role.

Inheritance files end in '.inherits' and are made up of 2 CSV fields.

* Role name
* Inherits: one role to inherit rules from

```
# Role,Inherits
jane,user
```


#### Rules

Rule files end in '.rules' and are made up of 4 CSV fields:

* Role name
* Controller name
* Action name
* Policy ('allow' or 'deny')

`example/reference/private/config/acl/Example.rules`:
```
# Role,Controller,Action,Policy
*,not-found,notFound,allow
user,example,hello,allow
```


### Web Server Configuration

This is a simple example of a web server configuration for Nginx.
It accepts regular HTTP traffic on port 80 and passes the PHP interpreting to PHP-FPM (PHP7.0).

```
server {
    listen 80;
    listen [::]:80 ipv6only=on;

    server_name localhost;
    root        '/www/freischutz/example/reference/reference';
    charset     utf-8;

    client_max_body_size 1M;
    underscores_in_headers on;

    gzip            on;
    gzip_vary       on;
    gzip_min_length 860;
    gzip_comp_level 1;
    gzip_types      text/plain application/json application/xml;

    try_files $uri $uri/ @rewrite;

    location @rewrite {
        rewrite ^/((.*?))$ /public/index.php?_url=/$1;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;

        fastcgi_buffers     256 16k;

        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_param PATH_INFO     $request_uri;
        fastcgi_param SCRIPT_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_param SCRIPT_FILENAME   $document_root$fastcgi_script_name;
     }
}
```

