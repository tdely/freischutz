Freischutz
==========
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036/mini.png)](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036)

PHP framework for RESTful APIs, built using Phalcon.

Features
--------

* Several authentication schemes
  * Hawk, supports storing nonces in file, database, or cache.
  * Basic authentication.
  * Bearer token, supports JWT type.
* Access control through ACL
  * Read from files or database.
* User system decoupled from validation/ACL schemes
  * Read from files, config, or database.
* Caching with multiple supported backends
  * Any \Phalcon\Cache\Backend class may be used.
  * Able to set caching of ACL, routes, and users through config.


Requirements
------------

* PHP >= 7.0.0
* Phalcon >= 3.0.1
* Webserver

If you are going to use a database:

* Database (MySQL/MariaDB/PostgreSQL/SQLite)
* PHP database adapter
