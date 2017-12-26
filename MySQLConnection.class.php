<?php

    // dependecy checks
    if (in_array('mysql', get_loaded_extensions()) === false) {
        throw new Exception('MySQL extension needs to be installed.');
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
     *     // output resource object
     *     $resource = MySQLConnection::getLink();
     *     print_r($resource);
     *     exit(0);
     * </code>
     */
    abstract class MySQLConnection
    {
        /**
         * _analytics
         * 
         * Array of query-type frequencies.
         * 
         * @var     array
         * @access  protected
         */
        protected static $_analytics = array(
            'deletes' => 0,
            'explains' => 0,
            'inserts' => 0,
            'selects' => 0,
            'shows' => 0,
            'updates' => 0,
            'uses' => 0
        );

        /**
         * _benchmark
         * 
         * @var     boolean (default: false)
         * @access  protected
         */
        protected static $_benchmark = false;

        /**
         * _inserted
         * 
         * @var     integer
         * @access  protected
         */
        protected static $_inserted;

        /**
         * _queries
         * 
         * Array contain MySQLQuery objects, useful for logging and performance
         * measurements.
         * 
         * @var     array (default: array())
         * @access  protected
         */
        protected static $_queries = array();

        /**
         * _resource
         * 
         * A resource link to a persistant mysql connection.
         * 
         * @var     resource
         * @access  protected
         */
        protected static $_resource;

        /**
         * _timeout
         * 
         * @var     integer (default: 5)
         * @access  protected
         */
        protected static $_timeout = 5;

        /**
         * getCumulativeQueryDuration
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getCumulativeQueryDuration()
        {
            $cumulativeTime = 0;
            foreach (self::$_queries as $query) {
                $cumulativeTime += $query->getDuration();
            }
            return $cumulativeTime;
        }

        /**
         * getNumberOfDeleteQueries
         * 
         * Returns the number of 'delete' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfDeleteQueries()
        {
            return self::$_analytics['deletes'];
        }

        /**
         * getNumberOfExplainQueries
         * 
         * Returns the number of 'explain' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfExplainQueries()
        {
            return self::$_analytics['explains'];
        }

        /**
         * getInsertedId
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getInsertedId()
        {
            return self::$_resource->insert_id;
        }

        /**
         * getNumberOfInsertQueries
         * 
         * Returns the number of 'inserts' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfInsertQueries()
        {
            return self::$_analytics['inserts'];
        }

        /**
         * getLink
         * 
         * @access  public
         * @static
         * @return  mysqli
         */
        public static function getLink()
        {
            return self::$_resource;
        }

        /**
         * getQueries
         * 
         * Returns an array of metric-focused MySQLQuery objects, useful for
         * measuring query performance.
         * 
         * @access  public
         * @static
         * @param   boolean $format (default: true)
         * @return  array
         */
        public static function getQueries($format = true)
        {
            // raw response
            if ($format === false) {
                return self::$_queries;
            }

            // formatted response
            $queries = array();
            foreach (self::$_queries as $query) {
                $queries[] = array(
                    'duration' => $query->getDuration(),
                    'statement' => $query->getStatement(),
                    'type' => $query->getType()
                );
            }
            return $queries;
        }

        /**
         * getNumberOfSelectQueries
         * 
         * Returns the number of 'select' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfSelectQueries()
        {
            return self::$_analytics['selects'];
        }

        /**
         * getNumberOfShowQueries
         * 
         * Returns the number of 'show' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfShowQueries()
        {
            return self::$_analytics['shows'];
        }

        /**
         * getStats
         * 
         * @access  public
         * @static
         * @return  array
         */
        public static function getStats()
        {
            $stats = self::$_analytics;
            $stats['total'] = count(self::$_queries);
            return $stats;
        }

        /**
         * getNumberOfUpdateQueries
         * 
         * Returns the number of 'update' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfUpdateQueries()
        {
            return self::$_analytics['updates'];
        }

        /**
         * getNumberOfUseQueries
         * 
         * Returns the number of 'use' statements made.
         * 
         * @access  public
         * @static
         * @return  integer
         */
        public static function getNumberOfUseQueries()
        {
            return self::$_analytics['uses'];
        }

        /**
         * getResource
         * 
         * @access  public
         * @static
         * @param   array $config
         * @param   boolean $benchmark (default: false)
         * @return  void
         */
        public static function init(array $config, $benchmark = false)
        {
            // init setting
            ini_set('mysql.connect_timeout', self::$_timeout);

            // benchmark setting (for logging duration)
            self::$_benchmark = $benchmark;

            // resource connection
            $resource = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database'],
                $config['port']
            );
            if ($resource === false) {
                throw new Exception(
                    'Couldn\'t establish connection: ' . $resource->error . '.'
                );
            }
            self::$_resource = $resource;
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
        public static function log(MySQLQuery $mySQLQuery)
        {
            array_push(self::$_queries, $mySQLQuery);
            $type = $mySQLQuery->getType();
            if ($type === 'delete') {
                ++self::$_analytics['deletes'];
            } elseif ($type === 'explain') {
                ++self::$_analytics['explains'];
            } elseif ($type === 'insert') {
                ++self::$_analytics['inserts'];
            } elseif ($type === 'select') {
                ++self::$_analytics['selects'];
            } elseif ($type === 'show') {
                ++self::$_analytics['shows'];
            } elseif ($type === 'update') {
                ++self::$_analytics['updates'];
            } elseif ($type === 'use') {
                ++self::$_analytics['uses'];
            }

            // If queries ought to be benchmarked
            if (self::$_benchmark === true) {
                error_log(
                    $mySQLQuery->getDuration() . ' - ' .
                    $mySQLQuery->getStatement()
                );
            }
        }

        /**
         * setTimeout
         * 
         * @access  public
         * @static
         * @param   integer $timeout
         * @return  void
         */
        public static function setTimeout($timeout)
        {
            self::$_timeout = $timeout;
        }
    }
