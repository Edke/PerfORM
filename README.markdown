
PerfORM - PHP object-relation mapping based on David Grudl's Dibi layer & Nette Framework
========================================================================================

Description to be filled.



Features
--------

To be filled.



Requirements
------------

* PHP 5.2 and higher
* Nette Framework
* Dibi abstraction layer
* Support only for PostgeSQL database
* FSHL for syntax highlighting of SQL code
* memcached support is optional, but highly recommended



Instalation
-----------

This guide assumes that you have your Nette application with dibi layer working and that you have
basic knowledge about Nette application project structure. Steps to integrate PerfORM into your
Nette project may vary, this is the way I suggest to use:

 1. put libs/PerfORM folder into your application's libs folder
 2. create folder bin and put bin/perform.php into it
 3. copy app/ConsoleModule to your app folder (hope that it won't collide with your Modules)
 4. download latest FSHL package (http://code.google.com/p/fshl/downloads/list) and
    unpack to your libs folder
 5. copy libs/fshl/out/ANSI_UTF8_output.php into your libs/fshl/out folder
 6. add this line to your Nette RobotLoader configuration:

    $robot->ignoreDirs= '.*, *.old, *.bak, *.tmp, temp, fshl_cache';

 7. add configuration to your application's config.ini (memcache):

    perform.memcache_host= localhost
    perform.memcache_port= 11211
    perform.cache_prefix= perform
    perform.advertisememcached= true

    perform.modelCache = %appDir%/PerfORM

    perform.storage.driver = sqlite
    perform.storage.database = %appDir%/PerfORM/storage.sdb
    perform.storage.profiler = true

 8. create modelCache folder as set in config (default is app/PerfORM) and
    make it writable for webserver or your user when cli management is used
 9. remove RobotLoader cache
10. create base models for your application, put them anywhere in app folder
    (usualy in app/models, app/YourModule/models etc.)
11. run PerfORM management SQL tool's syncdb to create database structure
12. use models in your application



License
-------

PerfORM will be released under open source licence



Documentation and Examples
--------------------------

To be filled.



Forum
-----

http://forum.nettephp.com/cs/3368-koncept-orm-postavenom-na-skvelom-dibi-a-inspirovanym-django-model


