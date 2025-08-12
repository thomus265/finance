<?php
class Database {

    protected $connection;
    protected $query;
    protected $show_errors = TRUE;
    protected $query_closed = TRUE;
    public $query_count = 0;

    public function __construct(
        $dbhost = null,
        $dbuser = null,
        $dbpass = null,
        $dbname = null,
        $charset = "utf8",
        $port = 3306
    ) {
        // Load configuration from environment variables or use defaults
        $dbhost = $dbhost ?: $this->getEnvVar('DB_HOST', 'localhost');
        $dbuser = $dbuser ?: $this->getEnvVar('DB_USER', 'root');
        $dbpass = $dbpass ?: $this->getEnvVar('DB_PASS', '');
        $dbname = $dbname ?: $this->getEnvVar('DB_NAME', 'finance_system');
        $port = $this->getEnvVar('DB_PORT', $port);

        // Log connection attempt (without password)
        error_log("Database: Attempting connection to $dbhost:$port as $dbuser to database $dbname");

        try {
            $this->connection = new mysqli($dbhost, $dbuser, $dbpass, $dbname, $port);

            if ($this->connection->connect_error) {
                $error_msg = "Failed to connect to MySQL - " . $this->connection->connect_error;
                error_log("Database Error: $error_msg");

                // Provide helpful troubleshooting information
                $this->provideTroubleshootingInfo($dbhost, $port, $dbuser, $dbname);

                die($error_msg);
            }

            $this->connection->set_charset($charset);
            error_log("Database: Successfully connected to MySQL database '$dbname'");

        } catch (Exception $e) {
            $error_msg = "Database connection exception: " . $e->getMessage();
            error_log("Database Error: $error_msg");
            die($error_msg);
        }
    }

    /**
     * Get environment variable with fallback
     */
    private function getEnvVar($key, $default = null) {
        // Check $_ENV first, then getenv()
        $value = $_ENV[$key] ?? getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Provide troubleshooting information when connection fails
     */
    private function provideTroubleshootingInfo($host, $port, $user, $database) {
        error_log("Database Troubleshooting Info:");
        error_log("- Host: $host");
        error_log("- Port: $port");
        error_log("- User: $user");
        error_log("- Database: $database");
        error_log("- Check if MySQL service is running");
        error_log("- Check if database '$database' exists");
        error_log("- Verify credentials are correct");
        error_log("- For XAMPP: Start MySQL service in XAMPP Control Panel");
    }


    public function query($query) {
        if (!$this->query_closed) {
            $this->query->close();
        }
        if ($this->query = $this->connection->prepare($query)) {
            if (func_num_args() > 1) {
                $x = func_get_args();
                $args = array_slice($x, 1);
                $types = '';
                $args_ref = array();
                foreach ($args as $k => &$arg) {
                    if (is_array($args[$k])) {
                        foreach ($args[$k] as $j => &$a) {
                            $types .= $this->_gettype($args[$k][$j]);
                            $args_ref[] = &$a;
                        }
                    } else {
                        $types .= $this->_gettype($args[$k]);
                        $args_ref[] = &$arg;
                    }
                }
                array_unshift($args_ref, $types);
                call_user_func_array(array($this->query, 'bind_param'), $args_ref);
            }
            $this->query->execute();
            if ($this->query->errno) {
                $this->error('Unable to process MySQL query (check your params) - ' . $this->query->error);
            }
            $this->query_closed = FALSE;
            $this->query_count++;
        } else {
            $this->error('Unable to prepare MySQL statement (check your syntax) - ' . $this->connection->error);
        }
        return $this;
    }


    public function fetchAll($callback = null) {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            $r = array();
            foreach ($row as $key => $val) {
                $r[$key] = $val;
            }
            if ($callback != null && is_callable($callback)) {
                $value = call_user_func($callback, $r);
                if ($value == 'break') break;
            } else {
                $result[] = $r;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
        return $result;
    }

    public function fetchArray() {
        $params = array();
        $row = array();
        $meta = $this->query->result_metadata();
        while ($field = $meta->fetch_field()) {
            $params[] = &$row[$field->name];
        }
        call_user_func_array(array($this->query, 'bind_result'), $params);
        $result = array();
        while ($this->query->fetch()) {
            foreach ($row as $key => $val) {
                $result[$key] = $val;
            }
        }
        $this->query->close();
        $this->query_closed = TRUE;
        return $result;
    }

    public function close() {
        return $this->connection->close();
    }

    public function numRows() {
        $this->query->store_result();
        return $this->query->num_rows;
    }

    public function affectedRows() {
        return $this->query->affected_rows;
    }

    public function lastInsertID() {
        return $this->connection->insert_id;
    }

    public function error($error) {
        if ($this->show_errors) {
            exit($error);
        }
    }

    private function _gettype($var) {
        if (is_string($var)) return 's';
        if (is_float($var)) return 'd';
        if (is_int($var)) return 'i';
        return 'b';
    }

}
?>