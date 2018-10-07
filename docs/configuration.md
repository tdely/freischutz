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
* log_destination: _(string)_ file path to log to, or 'syslog' to use syslog, **default syslog**.
* log_name: _(string)_ name to log under (e.g. syslog ident) **default freischutz**.
* log_level: _(string)_ granularity of log messages (available: debug,info,notice,warning,error,critical,alert,emergency; unknown value defaults to error), **default error**.
* authenticate: _(string)_ authentication mechanism to allow in CSV (available: basic,hawk,jwt), **default false**.
* metadata_adapter: _(string)_ A \Phalcon\Mvc\Model\Metadata\ to use for storing model metadata (class name only), **default memory**
* cache_adapter: _(string)_ A \Phalcon\Cache\Backend\ to use for caching (class name only), **default false**.
* cache_lifetime: _(int)_ time in seconds that cached data is kept, **default 60**.
* cache_parts: _(string)_ one or more parts to cache in CSV (available: users,acl,routes), **default false**.

### basic_auth
Required section **if** Basic authentication is enabled through application->authenticate.
* realm: _(string)_ Authentication realm, **default freischutz**.

### hawk
Required section **if** Hawk authentication is enabled through application->authenticate.
* algorithms: _(string)_ one or more algorithms to allow in CSV, **default sha256**.
* expire: _(int)_ time in seconds from request creation until considered expired, **default 60**.
* storage: _(file|database|cache)_ nonce storage backend, **default file**.
* disclose: _(boolean)_ disclose issue in response when validation fails, **default false**.
* nonce_dir: _(string)_ path to nonce file directory, **default tmp**.
* nonce_model: _(string)_ nonce model class with full namespace including leading backslash (e.g. \Example\Model\Nonce), **required if** `storage = database`.

### bearer
Required section **if** Bearer token authentication is enabled through application->authenticate.
* disclose: _(boolean)_ disclose issue in response when validation fails, **default false**.
* types: _(string)_ allow bearer token types in CSV, **default jwt**.

### jwt
Required section **if** JWT is enabled through bearer->types.
* claims: _(string)_ required claims in CSV (sub, exp and iat are **always** required), **default aud,iss**.
* grace: _(int)_ grace period in seconds for expire (exp) and not before (nbf) checks, **default 0**.
* aud: _(string)_ allowed audiences in CSV, **default freischutz**.
* iss: _(string)_ allowed issuers in CSV, **default freischutz**.

### acl
Optional section.
* enable: _(boolean)_ use ACL, **default false**.
* default_policy: _(allow|deny)_ default ACL policy, **default deny**.
* backend: _(file|database)_ ACL backend, **default file**.
* dir: _(string)_ path to ACL file directory, **required if** `backend = file`.
* di_share: _(boolean)_ enable ACL as a shared service in the dependency injector, **default false**.
* role_model: _(string)_ role model class with full namespace including leading backslash (e.g. \Example\Model\AclRole), **required if** `backend = database`.
* inherit_model: _(string)_ role inheritance model class with full namespace including leading backslash (e.g. \Example\Model\AclInherit), **required if** `backend = database`.
* resource_model: _(string)_ resource model class with full namespace including leading backslash (e.g. \Example\Model\AclResource), **required if** `backend = database`.
* rule_model: _(string)_ rule model class with full namespace including leading backslash (e.g. \Example\Model\AclRule), **required if** `backend = database`.

### users
Required section **if** users_backend is set to config under application.
Each key-value pair represents one user: `user_id = password`.


### Variable sections
When using cache_adapter or metadata_adapter (other than Memory), parameters
will be loaded from sections of the same name (lower case). Setting
`cache_adapter = Redis` will enable the use of Redis caching, and requires the
section 'redis' to be set with connection parameters. `metadata_adapter = Redis`
will use the same 'redis' section.

You could also create your own section for setting to use in your application.
