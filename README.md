Freischutz
==========
[![build](https://gitlab.com/tdely/freischutz/badges/master/build.svg)](https://gitlab.com/tdely/freischutz/commits/master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036/mini.png)](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036)

PHP framework for RESTful APIs, built using Phalcon.

Configuration Options
---------------------

### application
Required section.
* base_uri: _(string)_ shared URI prefix for all routes.
* strict_host_check: _(boolean)_ strict host name validation, **default false**.
* routes_dir: _(string)_ path to routes file directory, **required**.
* databases_dir: _(string)_ path to database settings directory, **required**.
* controller_namespace: _(string)_ namespace in which to search for controllers, **default Freischutz\Controllers**.
* users_backend: _(file|config|database)_ users storage backend, **default file**.
* users_dir: _(string)_ path to users file directory, **required if** `users_backend = file`.
* users_model: _(string)_ users model class with full namespace including leading backslash (e.g. \Example\Model\User), **required if** `users_backend = database`.
* authenticate: _(string)_ authentication mechanism to allow in CSV (available: hawk), **default false**.
* metadata_adapter: _(string)_ A \Phalcon\Mvc\Model\Metadata\ to use for storing model metadata (class name only), **default memory**
* cache_adapter: _(string)_ A \Phalcon\Cache\Backend\ to use for caching (class name only), **default false**.
* cache_lifetime: _(int)_ time in seconds that cached data is kept, **default 60**.
* cache_parts: _(string)_ one or more parts to cache in CSV (available: users,acl,routes), default **false**.

### hawk
Required section **if** Hawk is enabled through application->authenticate.
* algorithms: _(string)_ one or more algorithms to allow in CSV, **default sha256**.
* expire: _(int)_ time in seconds from request creation until considered expired, **default 60**.
* storage: _(file|database|cache)_ nonce storage backend, **default file**.
* disclose: _(boolean)_ disclose issue in response when validation fails, **default false**.
* nonce_dir: _(string)_ path to nonce file directory, **default tmp**.
* nonce_model: _(string)_ nonce model class with full namespace including leading backslash (e.g. \Example\Model\Nonce), **required if** `storage = database`.

### acl
Optional section.
* enable: _(boolean)_ use ACL, **default false**.
* backend: _(file|database)_ ACL backend, **default file**.
* dir: _(string)_ path to ACL file directory, **required**.
* role_model: _(string)_ role model class with full namespace including leading backslash (e.g. \Example\Model\Role), **required if** `backend = database`.
* inherit_model: _(string)_ role inheritance model class with full namespace including leading backslash (e.g. \Example\Model\Inherit), **required if** `backend = database`.
* resource_model: _(string)_ resource model class with full namespace including leading backslash (e.g. \Example\Model\Resource), **required if** `backend = database`.
* rule_model: _(string)_ rule model class with full namespace including leading backslash (e.g. \Example\Model\Rule), **required if** `backend = database`.

### users
Required section **if** users_backend is set to config under application.
Each key-value pair represents one user: `user_id = password`.


Features
--------

* Hawk validation/authentication
  * Stores nonces in file, database, or cache.
* Access control through ACL
  * Read from files or database.
* User system decoupled from validation/ACL schemes
  * Read from files, config, or database.
* Caching with multiple supported backends
  * Any \Phalcon\Cache\Backend class may be used.
  * Able to set caching of ACL, routes, and users through config.


Planned
-------

* More automated integration tests
* More/improved documentation


Getting Started
---------------

### Requirements

* PHP 5.6, PHP 7
* Phalcon 3.0.1 (possibly 2.0.x if using PHP 5.6)
* Webserver
* Database (MySQL/MariaDB/PostgreSQL/SQLite)
* PHP database adapter
