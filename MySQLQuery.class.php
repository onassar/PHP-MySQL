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
     * MySQLQuery
     * 
     * Performs and measures queries against a persistant MySQL connection.
     * 
     * @author  Oliver Nassar <onassar@gmail.com>
     * @example
     * <code>
     *     // load dependencies
     *     require_once APP . '/vendors/PHP-MySQL/MySQLConnection.class.php';
     *     require_once APP . '/vendors/PHP-MySQL/MySQLQuery.class.php';
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
     *     // database select; query; output
     *     (new MySQLQuery('USE `mysql`'));
     *     $query = (new MySQLQuery('SELECT * FROM `user`'));
     *     print_r($query->getResults());
     *     exit(0);
     * </code>
     */
    class MySQLQuery
    {
        /**
         * _end
         * 
         * Microseconds marking the end of the query, after having run.
         * 
         * @var    float
         * @access protected
         */
        protected $_end;

        /**
         * _raw
         * 
         * Results after a query has been executed.
         * 
         * @var    mixed
         * @access protected
         */
        protected $_raw;

        /**
         * _results
         * 
         * Formatted results available for return and usage (not raw).
         * 
         * @var    mixed
         * @access protected
         */
        protected $_results;

        /**
         * _start
         * 
         * Microseconds marking the start of the query.
         * 
         * @var    float
         * @access protected
         */
        protected $_start;

        /**
         * _statement
         * 
         * SQL statement that will be/has been run.
         * 
         * @var    string
         * @access protected
         */
        protected $_statement;

        /**
         * _type
         * 
         * Type of query being run (select, update, etc.)
         * 
         * @var    string
         * @access protected
         */
        protected $_type;

        /**
         * __construct
         * 
         * @access public
         * @param  string $statement
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
                $resource = MySQLConnection::getLink();
                throw new Exception(
                    '"' . ($statement) . '": ' . ($resource->error) . '.'
                );
            }

            // log
            $this->_log();
        }

        /**
         * _format
         * 
         * Formats a raw result set if applicable.
         * 
         * @note   The complicated key / value logic below is to prevent using
         *         references within the array, as I was running into issues in
         *         last version of this method that I just do not want to deal
         *         with at the moment. So while it's a big uglier, it should
         *         work quite dependably.
         * @access protected
         * @return void
         */
        protected function _format()
        {
            $this->_results = $this->_raw;
            if (in_array($this->_type, array('explain', 'select'))) {
                $results = array();
                while($result = $this->_results->fetch_assoc()) {
                    $results[] = $result;
                }
                $this->_results = $results;
            } else if (in_array($this->_type, array('show'))) {
                $results = array();
                while($result = $this->_results->fetch_array()) {
                    $results[] = $result;
                }
                $lowercase = strtolower($this->_statement);
                if (strstr($lowercase, 'show tables') !== false) {
                    foreach ($results as $key => $value) {
                        $results[$key] = $value[0];
                    }
                } elseif (strstr($lowercase, 'show variables') !== false) {
                    foreach ($results as $key => $result) {
                        foreach ($result as $secondaryKey => $value) {
                            if (is_numeric($secondaryKey) === true) {
                                unset($results[$key][$secondaryKey]);
                            }
                        }
                    }
                } elseif (strstr($lowercase, 'show index') !== false) {
                    foreach ($results as $key => $result) {
                        foreach ($result as $secondaryKey => $value) {
                            if (is_numeric($secondaryKey) === true) {
                                unset($results[$key][$secondaryKey]);
                            }
                        }
                    }
                }
                $this->_results = $results;
            }
        }

        /**
         * _log
         * 
         * @access protected
         * @return void
         */
        protected function _log()
        {
            MySQLConnection::log($this);
        }

        /**
         * _run
         * 
         * @access protected
         * @param  string $statement
         * @return void
         */
        protected function _run($statement)
        {
            return MySQLConnection::getLink()->query($statement);
        }

        /**
         * getDuration
         * 
         * Returns query-execution duration.
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
         * getResults
         * 
         * Returns formatted results of the executed query.
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
         * getStatement
         * 
         * Returns the SQL statement issued.
         * 
         * @access public
         * @return string
         */
        public function getStatement()
        {
            return $this->_statement;
        }

        /**
         * getType
         * 
         * Returns the type of query run.
         * 
         * @access public
         * @return string
         */
        public function getType()
        {
            return $this->_type;
        }
    }
