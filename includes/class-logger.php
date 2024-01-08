<?php

/**
 *
 * @Class logger
 *
 * @Purpose: Logs text to a file
 *
 * @Author: Kevin Waterson
 *
 * @copyright PHPRO.ORG (2009)
 *
 * @example usage
 * $log = logger::getInstance();
 * $log->logfile = '/tmp/errors.log';
 * $log->write('An error has occured', __FILE__, __LINE__);
 *
 */
if (!class_exists('logger')) {
    class logger
    {

        /*** Declare instance ***/
        private static $instance = NULL;
        public $logfile;

        /**
         *
         * @Constructor is set to private to stop instantion
         *
         */
        private function __construct()
        {
        }

        /**
         *
         * @settor
         *
         * @access public
         *
         * @param string $name
         *
         * @param mixed $value
         *
         */
        public function __set($name, $value)
        {
            switch ($name) {
                case 'logfile':
                    $path = dirname($value);
                    $file = basename($value);
                    if (!is_dir($path)) {
                        mkdir($path, 0775, true);
                    }
                    if (!file_exists($name) || !is_writeable($file)) {
                        @fopen($name, "w");
                        // throw new Exception("$name is not a valid file path");
                    }
                    $this->logfile = $value;
                    break;

                default:
                    throw new Exception("$name cannot be set");
            }
        }

        /**
         *
         * @write to the logfile
         *
         * @access public
         *
         * @param string $message
         *
         * @param string $file The filename that caused the error
         *
         * @param int $line The line that the error occurred on
         *
         * @return number of bytes written, false other wise
         *
         */
        public function write($message, $log_file = null, $file = null, $line = null)
        {
            if (!is_null($log_file)) {
                $this->__set('logfile', TODAYS_LOG_PATH . '/' . $log_file . '.log');
                if (strpos($log_file, 'notification') !== false) {
                    $this->__set('logfile', TODAYS_NOTIFICATION_PATH . '/' . $log_file . '.log');
                }
            }

            $message = "\n" . date("d M, Y H:i:s", time()) . ":\t" . print_r($message, true) . "\n";
            return file_put_contents($this->logfile, $message, FILE_APPEND);
        }

        /**
         *
         * Return logger instance or create new instance
         *
         * @return object (PDO)
         *
         * @access public
         *
         */
        public static function getInstance()
        {
            if (!self::$instance) {
                self::$instance = new logger;
            }
            return self::$instance;
        }


        /**
         * Clone is set to private to stop cloning
         *
         */
        private function __clone()
        {
        }
    }
    /*** end of log class ***/
}
