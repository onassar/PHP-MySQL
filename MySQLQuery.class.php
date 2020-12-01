<?php

    // dependecy checks
    if (in_array('mysqli', get_loaded_extensions()) === false) {
        throw new Exception('mysqli module needs to be installed.');
    }

    // connection dependency
    if (class_exists('MySQLConnection') === false) {
        throw new Exception('MySQLConnection needs to be loaded.');
    }

    // load MySQLConnection dependency
    require_once 'MySQLConnection.class.php';

    /**
     * MySQLQuery
     * 
     * Performs and measures queries against a persistent MySQL connection.
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
     *     new MySQLQuery('USE `mysql`');
     *     $query = new MySQLQuery('SELECT * FROM `user`');
     *     print_r($query->getResults());
     *     exit(0);
     * </code>
     */
    class MySQLQuery
    {
        /**
         * _raw
         * 
         * Results after a query has been executed.
         * 
         * @access  protected
         * @var     mixed
         */
        protected $_raw;

        /**
         * _results
         * 
         * Formatted results available for return and usage (not raw).
         * 
         * @access  protected
         * @var     mixed
         */
        protected $_results;

        /**
         * _statement
         * 
         * SQL statement that will be/has been run.
         * 
         * @access  protected
         * @var     null|string (default: null)
         */
        protected $_statement = null;

        /**
         * _timestamps
         * 
         * @access  protected
         * @var     float
         */
        protected $_timestamps;

        /**
         * _type
         * 
         * Type of query being run (select, update, etc.)
         * 
         * @access  protected
         * @var     null|string (default: null)
         */
        protected $_type = null;

        /**
         * __construct
         * 
         * @access  public
         * @param   string $statement
         * @return  void
         */
        public function __construct(string $statement)
        {
            $this->_statement = $statement;
            $this->__setType();
            $this->__setTimestamp('start');
            $this->_raw = $this->_run($statement);
            $this->__setTimestamp('end');
            $this->_handleFailedStatementExecution();
            $this->_log();
        }

        /**
         * __setTimestamp
         * 
         * @access  private
         * @param   string $key
         * @return  void
         */
        private function __setTimestamp(string $key): void
        {
            $this->_timestamps[$key] = microtime(true);
        }

        /**
         * __setType
         * 
         * @access  private
         * @return  void
         */
        private function __setType(): void
        {
            $statement = $this->_statement;
            $type = strtolower(current(explode(' ', $statement)));
            $this->_type = $type;
        }

        /**
         * _handleFailedStatementExecution
         * 
         * @throws  Exception
         * @access  protected
         * @return  bool
         */
        protected function _handleFailedStatementExecution(): bool
        {
            if ($this->_raw !== false) {
                return false;
            }
            $connection = MySQLConnection::getConnection();
            $error = $connection->error;
            $msg = '"' . ($statement) . '": ' . ($error) . '.';
            throw new Exception($msg);
        }

        /**
         * _format
         * 
         * Formats a raw result set if applicable.
         * 
         * @note    The complicated key / value logic below is to prevent using
         *          references within the array, as I was running into issues in
         *          last version of this method that I just do not want to deal
         *          with at the moment. So while it's a big uglier, it should
         *          work quite dependably.
         * @access  protected
         * @return  void
         */
        protected function _format()
        {
            $this->_results = $this->_raw;
            if (in_array($this->_type, array('explain', 'select')) === true) {
                $results = array();
                while ($result = $this->_results->fetch_assoc()) {
                    $results[] = $result;
                }
                $this->_results = $results;
            } elseif (in_array($this->_type, array('show')) === true) {
                $results = array();
                while ($result = $this->_results->fetch_array()) {
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
         * @access  protected
         * @return  void
         */
        protected function _log(): void
        {
            MySQLConnection::log($this);
        }

        /**
         * _run
         * 
         * @see     https://www.php.net/manual/en/mysqli.query.php
         * @access  protected
         * @param   string $statement
         * @return  mysqli_result|bool
         */
        protected function _run(string $statement)
        {
            $connection = MySQLConnection::getConnection();
            $response = $connection->query($statement);
            return $response;
        }

        /**
         * getDuration
         * 
         * Returns query-execution duration.
         * 
         * @access  public
         * @return  float
         */
        public function getDuration(): float
        {
            $end = $this->_timestamps['end'];
            $start = $this->_timestamps['start'];
            $difference = $end - $start;
            $difference = round($difference, 4);
            return $difference;
        }

        /**
         * getResults
         * 
         * Returns formatted results of the executed query.
         * 
         * @access  public
         * @return  array
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
         * @access  public
         * @return  string
         */
        public function getStatement(): string
        {
            $statement = $this->_statement;
            return $statement;
        }

        /**
         * getType
         * 
         * @access  public
         * @return  string
         */
        public function getType(): string
        {
            $type = $this->_type;
            return $type;
        }
    }
