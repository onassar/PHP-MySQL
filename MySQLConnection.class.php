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
         * @var     null|mysqli (default: null)
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
            $queries = array();
            foreach ($queries as $query) {
                $duration = $query->getDuration();
                $statement = $query->getStatement();
                $type = $query->getType();
                $queries[] = compact('duration', 'statement', 'type');
            }
            return $queries;
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
         * @param   array $config
         * @param   bool $benchmark (default: false)
         * @return  void
         */
        public static function init(array $config, bool $benchmark = false)
        {
            // init setting
            ini_set('mysql.connect_timeout', self::$_timeout);

            // benchmark setting (for logging duration)
            self::$_benchmark = $benchmark;

            // connection connection
            $connection = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );
            if ($connection === false) {
                $error = $connection->error;
                $msg = 'Couldn\'t establish connection: ' . ($error) . '.';
                throw new Exception($msg);
            }
            self::$_connection = $connection;
        }

        /**
         * log
         * 
         * Stores local MySQL query reference and increments statistics.
         * 
         * @access  public
         * @static
         * @param   MySQLQuery $mySQLQuery
         * @return  void
         */
        public static function log(MySQLQuery $mySQLQuery): void
        {
            array_push(self::$_queries, $mySQLQuery);
            $type = $mySQLQuery->getType();
            if ($type === 'delete') {
                ++self::$_stats['deletes'];
            } elseif ($type === 'explain') {
                ++self::$_stats['explains'];
            } elseif ($type === 'insert') {
                ++self::$_stats['inserts'];
            } elseif ($type === 'select') {
                ++self::$_stats['selects'];
            } elseif ($type === 'show') {
                ++self::$_stats['shows'];
            } elseif ($type === 'update') {
                ++self::$_stats['updates'];
            } elseif ($type === 'use') {
                ++self::$_stats['uses'];
            }

            // If queries ought to be benchmarked
            if (self::$_benchmark === true) {
                $msg = $mySQLQuery->getDuration() . ' - ' . $mySQLQuery->getStatement();
                error_log($msg);
            }
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
