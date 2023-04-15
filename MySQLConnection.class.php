<?php

    // dependecy checks
    if (in_array('mysqli', get_loaded_extensions()) === false) {
        $msg = 'mysqli module needs to be installed';
        throw new Exception($msg);
    }

    /**
     * MySQLConnection
     * 
     * Manages the connection to a single MySQL server.
     * 
     * @author  Oliver Nassar <onassar@gmail.com>
     * @abstract
     * @example
     * <code>
     *     // load dependency
     *     require_once APP . '/vendors/PHP-MySQL/MySQLConnection.class.php';
     * 
     *     // database credentials and connection
     *     $database = array(
     *         'host' => 'localhost',
     *         'port' => 3306,
     *         'username' => '<username>',
     *         'password' => '<password>'
     *     );
     *     MySQLConnection::init($database);
     * 
     *     // output connection object
     *     $connection = MySQLConnection::getConnection();
     *     print_r($connection);
     *     exit(0);
     * </code>
     */
    abstract class MySQLConnection
    {
        /**
         * _benchmark
         * 
         * @access  protected
         * @var     bool (default: false)
         */
        protected static $_benchmark = false;

        /**
         * _connection
         * 
         * A connection link to a persistent mysql connection.
         * 
         * @access  protected
         * @var     null|mysqli|bool (default: null)
         */
        protected static $_connection = null;

        /**
         * _inserted
         * 
         * @access  protected
         * @var     null|int (default: null)
         */
        protected static $_inserted = null;

        /**
         * _queries
         * 
         * Array contain MySQLQuery objects, useful for logging and performance
         * measurements.
         * 
         * @access  protected
         * @var     array (default: array())
         */
        protected static $_queries = array();

        /**
         * _selectedDatabaseName
         * 
         * A string reference to the database that is currently selected, by
         * it's name. This is useful for app's whereby multiple databases are
         * used within the same app.
         * 
         * @access  protected
         * @var     null|string (default: null)
         */
        protected static $_selectedDatabaseName = null;

        /**
         * _stats
         * 
         * @access  protected
         * @var     array
         */
        protected static $_stats = array(
            'deletes' => 0,
            'explains' => 0,
            'inserts' => 0,
            'selects' => 0,
            'shows' => 0,
            'updates' => 0,
            'uses' => 0
        );

        /**
         * _timeout
         * 
         * @access  protected
         * @var     int (default: 5)
         */
        protected static $_timeout = 5;

        /**
         * _benchmark
         * 
         * @access  protected
         * @static
         * @param   MySQLQuery $mySQLQuery
         * @return  bool
         */
        protected static function _benchmark(MySQLQuery $mySQLQuery): bool
        {
            $benchmark = self::$_benchmark;
            if ($benchmark === false) {
                return false;
            }
            $duration = $mySQLQuery->getDuration();
            $statement = $mySQLQuery->getStatement();
            $msg = ($duration) . ' - ' . ($statement);
            error_log($msg);
            return true;
        }

        /**
         * _getStatementExecutionErrorMessage
         * 
         * @access  protected
         * @static
         * @return  string
         */
        protected static function _getStatementExecutionErrorMessage(): string
        {
            $connection = self::$_connection;
            $error = $connection->error ?? '(unknown)';
            $msg = 'Couldn\'t establish connection: ' . ($error);
            return $msg;
        }

        /**
         * _handleFailedInstantiation
         * 
         * @throws  Exception
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _handleFailedInstantiation(): bool
        {
            $connection = self::$_connection;
            if ($connection === false) {
                $msg = self::_getStatementExecutionErrorMessage();
                throw new Exception($msg);
            }
            return false;
        }

        /**
         * _trackStats
         * 
         * @access  protected
         * @static
         * @param   MySQLQuery $mySQLQuery
         * @return  bool
         */
        protected static function _trackStats(MySQLQuery $mySQLQuery): bool
        {
            $type = $mySQLQuery->getType();
            if ($type === 'delete') {
                ++self::$_stats['deletes'];
                return true;
            }
            if ($type === 'explain') {
                ++self::$_stats['explains'];
                return true;
            }
            if ($type === 'insert') {
                ++self::$_stats['inserts'];
                return true;
            }
            if ($type === 'select') {
                ++self::$_stats['selects'];
                return true;
            }
            if ($type === 'show') {
                ++self::$_stats['shows'];
                return true;
            }
            if ($type === 'update') {
                ++self::$_stats['updates'];
                return true;
            }
            if ($type === 'use') {
                ++self::$_stats['uses'];
                return true;
            }
            return false;
        }

        /**
         * getConnection
         * 
         * @access  public
         * @static
         * @return  mysqli
         */
        public static function getConnection(): mysqli
        {
            $connection = self::$_connection;
            return $connection;
        }

        /**
         * getCumulativeQueryDuration
         * 
         * @access  public
         * @static
         * @return  float
         */
        public static function getCumulativeQueryDuration(): float
        {
            $queries = self::$_queries;
            $duration = 0;
            foreach ($queries as $query) {
                $duration += $query->getDuration();
            }
            return $duration;
        }

        /**
         * getDatabaseName
         * 
         * @access  public
         * @static
         * @return  string
         */
        public static function getDatabaseName(): string
        {
            $selectedDatabaseName = self::$_selectedDatabaseName;
            return $selectedDatabaseName;
        }

        /**
         * getInsertedId
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getInsertedId(): int
        {
            $connection = self::$_connection;
            $insertedId = $connection->insert_id;
            $insertedId = (int) $insertedId;
            return $insertedId;
        }

        /**
         * getNumberOfDeleteQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfDeleteQueries(): int
        {
            $deletes = self::$_stats['deletes'];
            return $deletes;
        }

        /**
         * getNumberOfExplainQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfExplainQueries(): int
        {
            $explains = self::$_stats['explains'];
            return $explains;
        }

        /**
         * getNumberOfInsertQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfInsertQueries(): int
        {
            $inserts = self::$_stats['inserts'];
            return $inserts;
        }

        /**
         * getNumberOfSelectQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfSelectQueries(): int
        {
            $selects = self::$_stats['selects'];
            return $selects;
        }

        /**
         * getNumberOfShowQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfShowQueries(): int
        {
            $shows = self::$_stats['shows'];
            return $shows;
        }

        /**
         * getNumberOfUpdateQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfUpdateQueries(): int
        {
            $updates = self::$_stats['updates'];
            return $updates;
        }

        /**
         * getNumberOfUseQueries
         * 
         * @access  public
         * @static
         * @return  int
         */
        public static function getNumberOfUseQueries(): int
        {
            $uses = self::$_stats['uses'];
            return $uses;
        }

        /**
         * getQueries
         * 
         * Returns an array of metric-focused MySQLQuery objects, useful for
         * measuring query performance.
         * 
         * @access  public
         * @static
         * @param   bool $format (default: true)
         * @return  array
         */
        public static function getQueries(bool $format = true): array
        {
            // raw response
            $queries = self::$_queries;
            if ($format === false) {
                return $queries;
            }

            // formatted response
            $formattedQueries = array();
            foreach ($queries as $query) {
                $duration = $query->getDuration();
                $statement = $query->getStatement();
                $type = $query->getType();
                $formattedQueries[] = compact('duration', 'statement', 'type');
            }
            return $formattedQueries;
        }

        /**
         * getStats
         * 
         * @access  public
         * @static
         * @return  array
         */
        public static function getStats(): array
        {
            $stats = self::$_stats;
            $queries = self::$_queries;
            $total = count($queries);
            $stats['total'] = $total;
            return $stats;
        }

        /**
         * getResource
         * 
         * @access  public
         * @static
         * @param   array $configData
         * @param   bool $benchmark (default: false)
         * @return  void
         */
        public static function init(array $configData, bool $benchmark = false)
        {
            // init setting
            ini_set('mysql.connect_timeout', self::$_timeout);

            // benchmark setting (for logging duration)
            self::$_benchmark = $benchmark;
            self::$_selectedDatabaseName = $configData['database'];

            // Connect and handle any failed connections
            $host = $configData['host'];
            $username = $configData['username'];
            $password = $configData['password'];
            $database = $configData['database'];
            $port = $configData['port'];
            $args = array($host, $username, $password, $database, $port);

            // PlanetScale testing
            $role = \Config\Base::getRole();
            // if ($role === 'local') {
                $connection = new mysqli(... $args);
            // } elseif ($role === 'dev') {
            //     $connection = mysqli_init();
            //     $connection->ssl_set(NULL, NULL, '/etc/ssl/certs/ca-certificates.crt', NULL, NULL);
            //     $connection->real_connect(
            //         $host,
            //         $username,
            //         $password,
            //         $database
            //     );
            // }

            // Done
            self::$_connection = $connection;
            self::_handleFailedInstantiation();
        }

        /**
         * log
         * 
         * Stores local MySQL query reference and increments stats.
         * 
         * @access  public
         * @static
         * @param   MySQLQuery $mySQLQuery
         * @return  void
         */
        public static function log(MySQLQuery $mySQLQuery): void
        {
            array_push(self::$_queries, $mySQLQuery);
            self::_trackStats($mySQLQuery);
            self::_benchmark($mySQLQuery);
        }

        /**
         * selectDatabase
         * 
         * @access  public
         * @static
         * @param   null|string $databaseName
         * @return  bool
         */
        public static function selectDatabase(?string $databaseName): bool
        {
            if ($databaseName === null) {
                return false;
            }
            $selectedDatabaseName = self::$_selectedDatabaseName;
            if ($selectedDatabaseName === $databaseName) {
                return false;
            }
            $connection = self::$_connection;
            $connection->select_db($databaseName);
            self::$_selectedDatabaseName = $databaseName;
            return true;
        }

        /**
         * setTimeout
         * 
         * @access  public
         * @static
         * @param   int $timeout
         * @return  void
         */
        public static function setTimeout(int $timeout): void
        {
            self::$_timeout = $timeout;
        }
    }
