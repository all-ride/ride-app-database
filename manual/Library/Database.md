The database module provides a abstraction layer for most common database actions.
It's initialy written for the ORM module but can be used for other things as well.

## Database Manager

The database manager is the facade to this library.
It contains your connections, the available drivers and the definers.

See the [DatabaseManager](/admin/documentation/api/class/ride/library/database/DatabaseManager) API documentation for more information.

## Register A Database Connection

A connection is the combination of a machine name and a DSN.

The format of the DSN is _protocol://[username[:password]@]host[:port]/database_.

### Through Code

See the following sample code to add a connection:

    <?php

    use ride\library\database\DatabaseManager;
    use ride\library\database\Dsn;

    function foo(DatabaseManager $databaseManager) {
        $this->databaseManager->registerConnection('foo', new Dsn('mysql://foo:secret@localhost/foo_database'));
    }

### Through Configuration

Add a [parameter](/admin/documentation/manual/page/Core/Parameters) with the name _database.connection.foo where _foo_ is the machine name of your connection.
The value for this parameter is a DSN.

    database.connection.bar = "mysql://bar:secret@localhost/bar_database"

The name _default_ is reserved for the default connection.
It's special in the sense that it can hold a DSN or a name of another connection.

When there is only one connection registered, that one is automatically the default connection.

## Get A Database Connection

See the following sample code to obtain a connection:

    <?php

    use ride\library\database\DatabaseManager;

    function foo(DatabaseManager $databaseManager) {
        // the default connection
        $connection = $databaseManager->getConnection();

        // connection with the name 'foo'
        $connection = $databaseManager->getConnection('foo');
    }

## Execute Plain SQL

Once you have your connection, you can execute SQL statements on it:

    <?php

    use ride\library\database\driver\Driver;

    function bar(Driver $connection) {
        $result = $connection->execute('SELECT id, name FROM categories');
        foreach ($result as $row) {
            echo $row['id'] . ' ' . $row['name'];
        }
    }

See the [DatabaseResult](/admin/documentation/api/class/ride/library/database/result/DatabaseResult) API documentation for more information about a query result.
