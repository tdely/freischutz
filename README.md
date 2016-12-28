Freischutz
==========

PHP framework for RESTful APIs, using Phalcon.

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

### hawk
Optional section.
* enable: _(boolean)_ use Hawk validation, **default false**.
* algorithms: _(string)_ one or more allowed algorithms in CSV without spaces, **default sha256**.
* expire: _(int)_ time in seconds from request creation until considered expired, **default 60**.
* storage: _(file|database)_ nonce storage backend, **default file**.
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
Required section if users_backend is set to config under application.
Each key-value pair represents one user: `user_id = password`.


Features
--------

* Hawk validation/authentication
  * Stores nonces in file or database.
* Access control through ACL
  * Read from files or database.
* User system decoupled from validation/ACL schemes
  * Read from files, config, or database.


Planned
-------

* Caching service
  * Redis primarily, but the idea is to support multiple backends
  * Option to cache each separate viable component:
     * Users
     * Routes
     * ACL
     * Hawk nonces
* Some (semi-)automated tests
* Documentation
  * Available classes
     * Public methods
  * Getting started
     * Requirements
     * Building your API
