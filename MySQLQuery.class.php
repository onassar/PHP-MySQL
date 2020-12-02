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
     *     print_r($query->getFormattedResult());
     *     exit(0);
     * </code>
     */
    class MySQLQuery
    {
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
         * _statementFormattedResult
         * 
         * Formatted results available for return and usage (not raw).
         * 
         * @access  protected
         * @var     null|array (default: null)
         */
        protected $_statementFormattedResult = null;

        /**
         * _statementResult
         * 
         * Results after a query has been executed.
         * 
         * @access  protected
         * @var     null|mysqli_result|bool (default: null)
         */
        protected $_statementResult = null;

        /**
         * _timestamps
         * 
         * @access  protected
         * @var     array (default: array())
         */
        protected $_timestamps = array();

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
         * @param   null|string $databaseName (default: null)
         * @return  void
         */
        public function __construct(string $statement, ?string $databaseName = null)
        {
            MySQLConnection::selectDatabase($databaseName);
            $this->_statement = $statement;
            $this->__setType();
            $this->__setTimestamp('start');
            $this->__runStatement($statement);
            $this->__setTimestamp('end');
            $this->_handleFailedStatementExecution();
            $this->_log();
        }

        /**
         * __runStatement
         * 
         * @see     https://www.php.net/manual/en/mysqli.query.php
         * @access  private
         * @param   string $statement
         * @return  void
         */
        private function __runStatement(string $statement): void
        {
            $connection = MySQLConnection::getConnection();
            $result = $connection->query($statement);
            $this->_statementResult = $result;
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
         * _formatResult
         * 
         * Formats a raw result set if applicable.
         * 
         * @note    The complicated key / value logic below is to prevent using
         *          references within the array, as I was running into issues in
         *          last version of this method that I just do not want to deal
         *          with at the moment. So while it's a bit uglier, it should
         *          work quite reliably.
         * @access  protected
         * @return  bool
         */
        protected function _formatResult(): bool
        {
            /**
             * Explain and Select queries
             * 
             */
            $statementResult = $this->_statementResult;
            if (in_array($this->_type, array('explain', 'select')) === true) {
                $formattedResult = array();
                while ($result = $statementResult->fetch_assoc()) {
                    $formattedResult[] = $result;
                }
                $this->_statementFormattedResult = $formattedResult;
                return true;
            }

            /**
             * Show queries
             * 
             */
            if (in_array($this->_type, array('show')) === true) {
                $formattedResult = array();
                while ($result = $statementResult->fetch_array()) {
                    $formattedResult[] = $result;
                }
                $lowercase = strtolower($this->_statement);
                if (strstr($lowercase, 'show tables') !== false) {
                    foreach ($formattedResult as $key => $value) {
                        $formattedResult[$key] = $value[0];
                    }
                } elseif (strstr($lowercase, 'show variables') !== false) {
                    foreach ($formattedResult as $key => $result) {
                        foreach ($result as $secondaryKey => $value) {
                            if (is_numeric($secondaryKey) === true) {
                                unset($formattedResult[$key][$secondaryKey]);
                            }
                        }
                    }
                } elseif (strstr($lowercase, 'show index') !== false) {
                    foreach ($formattedResult as $key => $result) {
                        foreach ($result as $secondaryKey => $value) {
                            if (is_numeric($secondaryKey) === true) {
                                unset($formattedResult[$key][$secondaryKey]);
                            }
                        }
                    }
                }
                $this->_statementFormattedResult = $formattedResult;
                return true;
            }

            // Alternative query type
            return false;
        }

        /**
         * _getStatementExecutionErrorMessage
         * 
         * @access  protected
         * @return  string
         */
        protected function _getStatementExecutionErrorMessage(): string
        {
            $databaseName = MySQLConnection::getDatabaseName();
            $statement = $this->_statement;
            $connection = MySQLConnection::getConnection();
            $error = $connection->error;
            $msg = '"' . ($statement) . '": ' . ($error) . '.';
            $msg = ($msg) . ' Database name: ' . ($databaseName);
            return $msg;
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
            if ($this->_statementResult === false) {
                $msg = $this->_getStatementExecutionErrorMessage();
                throw new Exception($msg);
            }
            return false;
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
         * getFormattedResult
         * 
         * Returns formatted results of the executed query.
         * 
         * @access  public
         * @return  array
         */
        public function getFormattedResult()
        {
            if ($this->_statementFormattedResult === null) {
                $this->_formatResult();
            }
            $result = $this->_statementFormattedResult;
            return $result;
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
