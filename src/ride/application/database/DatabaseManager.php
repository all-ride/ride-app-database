<?php

namespace ride\application\database;

use ride\library\config\Config;
use ride\library\database\exception\DatabaseException;
use ride\library\database\DatabaseManager as LibDatabaseManager;
use ride\library\database\Dsn;
use ride\library\dependency\DependencyInjector;

use \Exception;

/**
 * Database manager with extension to load connections and drivers from the
 */
class DatabaseManager extends LibDatabaseManager {

    /**
     * Configuration value for the drivers
     * @var string
     */
    const PARAM_DRIVER = 'database.driver';

    /**
     * Configuration value for the connections
     * @var string
     */
    const PARAM_CONNECTION = 'database.connection';

    /**
     * Name of the default connection
     * @var string
     */
    const NAME_DEFAULT = 'default';

    /**
     * Instance of the dependency injector
     * @var ride\library\dependency\DependencyInjector
     */
    protected $dependencyInjector;

    /**
     * Instance of the configuration
     * @var ride\library\config\Config
     */
    protected $config;

    /**
     * Constructs a database manager and loads the drivers and connections
     * from the configuration
     * @param ride\library\config\Config $config Instance of the configuration
     * @return null
     */
    public function __construct(DependencyInjector $dependencyInjector, Config $config) {
        parent::__construct();

        $this->dependencyInjector = $dependencyInjector;
        $this->config = $config;

        $this->registerDrivers();
        $this->registerConnections();
    }

    /**
     * Loads the drivers from the configuration
     * @return null
     */
    protected function registerDrivers() {
        $drivers = $this->config->get(self::PARAM_DRIVER);
        if (!$drivers) {
            return;
        }

        foreach ($drivers as $protocol => $class) {
            parent::registerDriver($protocol, $class);
        }
    }

    protected function registerConnections() {
        $connections = $this->config->get(self::PARAM_CONNECTION);
        if (!$connections) {
            return;
        }

        $default = null;

        foreach ($connections as $name => $dsn) {
            try {
                parent::registerConnection($name, new Dsn($dsn));

                if ($name == self::NAME_DEFAULT) {
                    $default = $name;
                }
            } catch (DatabaseException $exception) {
                if ($name == self::NAME_DEFAULT) {
                    $default = $dsn;
                }
            }
        }

        if ($default) {
            parent::setDefaultConnectionName($default);
        }
    }

    /**
     * Sets the default connection
     * @param string $defaultConnectionName Name of the new default connection
     * @return null
     * @throws ride\library\database\exception\DatabaseException when the
     * connection name is invalid or when the connection does not exist
     */
    public function setDefaultConnectionName($defaultConnectionName) {
        parent::setDefaultConnectionName($defaultConnectionName);

        $this->config->set(self::PARAM_CONNECTION. '.' . self::NAME_DEFAULT, $this->defaultConnectionName);
    }

    /**
     * Registers a connection in the manager
     * @param string $name Name of the connection
     * @param Dsn $dsn DSN connection properties
     * @return null
     * @throws ride\library\database\exception\DatabaseException when the name
     * is invalid or already registered and connected
     * @throws ride\library\database\exception\DatabaseException when the
     * protocol has no driver available
     */
    public function registerConnection($name, Dsn $dsn) {
        parent::registerConnection($name, $dsn);

        $this->config->set(self::PARAM_CONNECTION. '.' . $name, (string) $dsn);
    }

    /**
     * Unregisters a connection from the manager
     * @param string $name Name of the connection
     * @return null
     * @throws ride\library\database\exception\DatabaseException when the
     * name is invalid
     * @throws ride\library\database\exception\DatabaseException when no
     * connection is registered with the provided name
     */
    public function unregisterConnection($name) {
        $defaultConnection = $this->defaultConnectionName;

        parent::unregisterConnection($name);

        $this->config->set(self::PARAM_CONNECTION. '.' . $name, null);

        if ($defaultConnection != $this->defaultConnectionName) {
            $this->config->set(self::PARAM_CONNECTION. '.' . self::NAME_DEFAULT, $this->defaultConnectionName);
        }
    }

    /**
     * Registers a database driver with it's protocol in the manager
     * @param string $protocol Database protocol of this driver
     * @param string $className Class name of the driver
     * @return null
     * @throws ride\library\database\exception\DatabaseException when the
     * protocol or class name is empty or invalid
     * @throws ride\library\database\exception\DatabaseException when the
     * database driver does not exist or is not a valid driver class
     */
    public function registerDriver($protocol, $className) {
        parent::registerDriver($protocol, $className);

        $this->config->set(self::PARAM_DRIVER . '.' . $protocol, $className);
    }

    /**
     * Unregisters a driver from the manager
     * @param string $protocol Protocol of the connection
     * @return null
     * @throws ride\library\database\exception\DatabaseException when the
     * protocol is invalid
     * @throws ride\library\database\exception\DatabaseException when no
     * driver is registered with the provided protocol
     */
    public function unregisterDriver($protocol) {
        parent::unregisterDriver($protocol);

        $this->config->set(self::PARAM_DRIVER . '.' . $protocol);
    }

    /**
     * Checks if a definer has been registered
     * @param string $protocol Protocol of the definer
     * @return boolean True if the definer exists, false otherwise
     */
    public function hasDefiner($protocol) {
        if (!is_string($protocol) || !$protocol) {
            throw new DatabaseException('Provided protocol is empty or invalid');
        }

        if (isset($this->definers[$protocol])) {
            return true;
        }

        try {
            $definer = $this->dependencyInjector->get('ride\\library\\database\\definition\\definer\\Definer', $protocol);

            $this->definers[$protocol] = $definer;

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

}