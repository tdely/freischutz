Freischutz
==========
[![build](https://gitlab.com/tdely/freischutz/badges/master/build.svg)](https://gitlab.com/tdely/freischutz/commits/master)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036/mini.png)](https://insight.sensiolabs.com/projects/f6cd23de-3b8f-4a48-a2d1-59aeee13f036)

PHP framework for RESTful APIs, built using Phalcon.

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


Requirements
------------

* PHP 5.6, PHP 7
* Phalcon 3.0.1 (possibly 2.0.x if using PHP 5.6)
* Webserver

If you are going to use a database:

* Database (MySQL/MariaDB/PostgreSQL/SQLite)
* PHP database adapter
