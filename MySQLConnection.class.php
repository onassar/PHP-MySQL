<?php

    // dependecy checks
    if (!in_array('mysql', get_loaded_extensions())) {
        throw new Exception('MySQL extension needs to be installed.');
    }

    /**
     * Abstract MySQLConnection class.
     * 
     * @abstract
     */
    abstract class MySQLConnection
    {
        /**
         * _deletes. Number of database 'delete' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_deletes = 0;

        /**
         * explains. Number of database 'explain' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_explains = 0;

        /**
         * inserts. Number of database 'insert' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_inserts = 0;

        /**
         * _inserted
         * 
         * @var int
         * @access protected
         */
        protected static $_inserted;

        /**
         * queries. Array contain MySQLQuery objects, useful for logging and
         *     performance measurements.
         * 
         * (default value: array())
         * 
         * @var array
         * @access protected
         */
        protected static $_queries = array();

        /**
         * resource. A resource link to a persistant mysql connection.
         * 
         * @var resource
         * @access protected
         */
        protected static $_resource;

        /**
         * selects. Number of database 'select' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_selects = 0;

        /**
         * selects. Number of database 'show' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_shows = 0;

        /**
         * updates. Number of database 'update' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_updates = 0;

        /**
         * uses. Number of database 'uses' calls made.
         * 
         * @var int
         * @access protected
         */
        protected static $_uses = 0;

        /**
         * getDeletes function. Returns the number of 'delete' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getDeletes()
        {
            return self::$_deletes;
        }

        /**
         * getExplains function. Returns the number of 'explain' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getExplains()
        {
            return self::$_explains;
        }

        /**
         * getInserted function.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getInserted()
        {
            return mysql_insert_id(self::$_resource);
        }

        /**
         * getQueries function. Returns an array of metric-focused MySQLQuery
         *     objects, useful for measuring query performance.
         * 
         * @access public
         * @static
         * @return array
         */
        public static function getQueries()
        {
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
         * getInserts function. Returns the number of 'inserts' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getInserts()
        {
            return self::$_inserts;
        }

        /**
         * getResource function.
         * 
         * @access public
         * @static
         * @param array $config
         * @return void
         */
        public static function init($config)
        {
            $resource = mysql_pconnect(
                ($config['host']) . ':' . ($config['port']),
                $config['username'],
                $config['password']
            );
            if ($resource === false) {
                throw new Exception('Couldn\'t establish connection: ' . mysql_error() . '.');
            }
            self::$_resource = $resource;
        }

        /**
         * getLink function.
         * 
         * @access public
         * @static
         * @return resource
         */
        public static function getLink()
        {
            return self::$_resource;
        }

        /**
         * getSelects function. Returns the number of 'select' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getSelects()
        {
            return self::$_selects;
        }

        /**
         * getShows function. Returns the number of 'show' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getShows()
        {
            return self::$_shows;
        }

        /**
         * getStats function.
         * 
         * @access public
         * @static
         * @return array
         */
        public static function getStats()
        {
            return array(
                'deletes' => self::$_deletes,
                'explains' => self::$_explains,
                'inserts' => self::$_inserts,
                'selects' => self::$_selects,
                'shows' => self::$_shows,
                'updates' => self::$_updates,
                'uses' => self::$_uses,
                'total' => count(self::$_queries)
            );
        }

        /**
         * getUpdates function. Returns the number of 'update' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getUpdates()
        {
            return self::$_updates;
        }

        /**
         * getUses function. Returns the number of 'use' statements made.
         * 
         * @access public
         * @static
         * @return int
         */
        public static function getUses()
        {
            return self::$_uses;
        }

        /**
         * log function. Stores local MySQL query reference and increments
         *     statistics.
         * 
         * @access public
         * @static
         * @param MySQLQuery $mySQLQuery MySQLQuery
         * @return void
         */
        public static function log(MySQLQuery $mySQLQuery)
        {
            array_push(self::$_queries, $mySQLQuery);
            $type = $mySQLQuery->getType();
            if ($type === 'delete') {
                ++self::$_deletes;
            } elseif ($type === 'explain') {
                ++self::$_explains;
            } elseif ($type === 'insert') {
                ++self::$_inserts;
            } elseif ($type === 'select') {
                ++self::$_selects;
            } elseif ($type === 'show') {
                ++self::$_shows;
            } elseif ($type === 'update') {
                ++self::$_updates;
            } elseif ($type === 'use') {
                ++self::$_uses;
            }
        }
    }

?>
