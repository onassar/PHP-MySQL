<?php

    // dependecy checks
    if (!in_array('mysql', get_loaded_extensions())) {
        throw new Exception('MySQL extension needs to be installed.');
    }

    // connection dependency
    if (!class_exists('MySQLConnection')) {
        throw new Exception('MySQLConnection needs to be loaded.');
    }

    // load MySQLConnection dependency
    require_once 'MySQLConnection.class.php';

    /**
     * MySQLQuery class.
     * 
     * Performs and measures queries against a persistant MySQL connection.
     * 
     * @author  Oliver Nassar <onassar@gmail.com>
     * @example
     * <code>
     *     require_once APP . '/vendors/PHP-MySQL/MySQLConnection.class.php';
     *     require_once APP . '/vendors/PHP-MySQL/MySQLQuery.class.php';
     *     $database = array(
     *         'host' => 'localhost',
     *         'port' => 3306,
     *         'username' => '<username>',
     *         'password' => '<password>'
     *     );
     *     MySQLConnection::init($database);
     *     (new MySQLQuery('USE `mysql`'));
     *     $query = (new MySQLQuery('SELECT * FROM `user`'));
     *     print_r($query->getResults());
     *     exit(0);
     * </code>
     */
    class MySQLQuery
    {
        /**
         * _end. Microseconds marking the end of the query, after having run.
         * 
         * @var float
         * @access protected
         */
        protected $_end;

        /**
         * _raw. Results after a query has been executed.
         * 
         * @var mixed
         * @access protected
         */
        protected $_raw;

        /**
         * _results. Formatted results available for return and usage (not raw).
         * 
         * @var mixed
         * @access protected
         */
        protected $_results;

        /**
         * _start. Microseconds marking the start of the query.
         * 
         * @var float
         * @access protected
         */
        protected $_start;

        /**
         * _statement. SQL statement that will be/has been run.
         * 
         * @var string
         * @access protected
         */
        protected $_statement;

        /**
         * _type. Type of query being run (select, update, etc.)
         * 
         * @var string
         * @access protected
         */
        protected $_type;

        /**
         * __construct function.
         * 
         * @access public
         * @param string $statement
         * @return void
         */
        public function __construct($statement)
        {
            // metrics/run
            $this->_statement = $statement;
            $this->_start = microtime(true);
            $this->_raw = $this->_run($statement);
            $this->_end = microtime(true);
            $this->_type = strtolower(current(explode(' ', $this->_statement)));

            // query failed
            if ($this->_raw === false) {
                throw new Exception('"' . ($statement) . '": ' . mysql_error() . '.');
            }

            // log
            $this->_log();
        }

        /**
         * _format function. Formats a raw result set if applicable.
         * 
         * @access protected
         * @return void
         */
        protected function _format()
        {
            $this->_results = $this->_raw;
            if (in_array($this->_type, array('explain', 'select', 'show'))) {
                $results = array();
                while ($results[] = mysql_fetch_assoc($this->_raw)) { }

                // since results will add an extra empty value
                array_pop($results);

                // show based (used for database table retrieval)
                if ($this->_type === 'show') {

                    // remove nested tree structure (eg. for `SHOW TABLES`)
                    foreach ($results as &$property) {
                        $property = array_shift($property);
                    }
                }

                // store results
                $this->_results = $results;
            }
        }

        /**
         * _log function.
         * 
         * @access protected
         * @return void
         */
        protected function _log()
        {
            MySQLConnection::log($this);
        }

        /**
         * _run function.
         * 
         * @access protected
         * @param string $statement
         * @return void
         */
        protected function _run($statement)
        {
            return mysql_query($statement, MySQLConnection::getLink());
        }

        /**
         * getDuration function. Returns query-execution duration.
         * 
         * @access public
         * @return float
         */
        public function getDuration()
        {
            $difference = $this->_end - $this->_start;
            return round($difference, 4);
        }

        /**
         * getResults function. Returns formatted results of the executed query.
         * 
         * @access public
         * @return array
         */
        public function getResults()
        {
            if ($this->_results === null) {
                $this->_format();
            }
            return $this->_results;
        }

        /**
         * getStatement function. Returns the SQL statement issued.
         * 
         * @access public
         * @return string
         */
        public function getStatement()
        {
            return $this->_statement;
        }

        /**
         * getType function. Returns the type of query run.
         * 
         * @access public
         * @return string
         */
        public function getType()
        {
            return $this->_type;
        }
    }

?>
