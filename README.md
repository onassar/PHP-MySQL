PHP MySQL
===

PHP-MySQL provides two classes which aim to make the execution of queries
against a MySQL engine more organized and powerful. By routing all queries
through a connection wrapper and an instantiable **MySQLQuery** class, queries
can be analyzed, recalled, and their connections shared, more efficiently.

As an example, the **getDuration** method on a **Query** object gives you the
ability to determine a queries execution length/duration. Similarly, the static
**MySQLConnection** method **getStats** provides statistics on the number of
queries executed grouped by type, as well as a collection of the total queries
executed during the length of the request.

**Note:** Neither class filters, encodes or secures the queries being run. This
was done intentionally to decouple data processing logic from database access.

### Sample MySQL Connection

``` php
<?php

    // load dependency
    require_once APP . '/vendors/PHP-MySQL/MySQLConnection.class.php';
    
    // database credentials and connection
    $database = array(
        'host' => 'localhost',
        'port' => 3306,
        'username' => '<username>',
        'password' => '<password>'
    );
    MySQLConnection::init($database);
    
    // output resource object
    $resource = MySQLConnection::getLink();
    print_r($resource);
    exit(0);

```

### Sample MySQL Query

``` php
<?php

    // load dependencies
    require_once APP . '/vendors/PHP-MySQL/MySQLConnection.class.php';
    require_once APP . '/vendors/PHP-MySQL/MySQLQuery.class.php';
    
    // database credentials and connection
    $database = array(
        'host' => 'localhost',
        'port' => 3306,
        'username' => '<username>',
        'password' => '<password>'
    );
    MySQLConnection::init($database);
    
    // database select; query; output
    new MySQLQuery('USE `mysql`');
    $query = new MySQLQuery('SELECT * FROM `user`');
    print_r($query->getResults());
    exit(0);

```
