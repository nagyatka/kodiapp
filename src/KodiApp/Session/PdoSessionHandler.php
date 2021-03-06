<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 09.
 * Time: 19:27
 */

namespace KodiApp\Session;

/**
 * PdoSessionHandler.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Michael Williams <michael.williams@funsational.com>
 */
class PdoSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \PDO PDO instance.
     */
    private $pdo;
    /**
     * @var array Database options.
     */
    private $dbOptions;
    /**
     * Constructor.
     *
     * List of available options:
     *  * db_table: The name of the table [required]
     *  * db_id_col: The column where to store the session id [default: sess_id]
     *  * db_data_col: The column where to store the session data [default: sess_data]
     *  * db_time_col: The column where to store the timestamp [default: sess_time]
     *
     * @param \PDO  $pdo       A \PDO instance
     * @param array $dbOptions An associative array of DB options
     *
     * @throws \InvalidArgumentException When "db_table" option is not provided
     */
    public function __construct(\PDO $pdo, array $dbOptions = array())
    {
        if (!array_key_exists('db_table', $dbOptions)) {
            throw new \InvalidArgumentException('You must provide the "db_table" option for a PdoSessionStorage.');
        }
        $this->pdo = $pdo;
        $this->dbOptions = array_merge(array(
            'db_id_col'   => 'sess_id',
            'db_data_col' => 'sess_data',
            'db_time_col' => 'sess_time',
            'db_ip_col'   => 'sess_ip', // Módosított
        ), $dbOptions);
    }
    /**
     * {@inheritDoc}
     */
    public function open($path, $name)
    {
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function close()
    {
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function destroy($id)
    {
        // get table/column
        $dbTable = $this->dbOptions['db_table'];
        $dbIdCol = $this->dbOptions['db_id_col'];
        // delete the record associated with this id
        $sql = "DELETE FROM $dbTable WHERE $dbIdCol = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
        }
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function gc($lifetime)
    {
        // get table/column
        $dbTable   = $this->dbOptions['db_table'];
        $dbTimeCol = $this->dbOptions['db_time_col'];
        // delete the session records that have expired
        $sql = "DELETE FROM $dbTable WHERE $dbTimeCol < :time";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':time', time() - $lifetime, \PDO::PARAM_INT);
            $stmt->execute();
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to manipulate session data: %s', $e->getMessage()), 0, $e);
        }
        return true;
    }
    /**
     * {@inheritDoc}
     */
    public function read($id)
    {
        // get table/columns
        $dbTable   = $this->dbOptions['db_table'];
        $dbDataCol = $this->dbOptions['db_data_col'];
        $dbIdCol   = $this->dbOptions['db_id_col'];
        try {
            $sql = "SELECT $dbDataCol FROM $dbTable WHERE $dbIdCol = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
            $stmt->execute();
            // it is recommended to use fetchAll so that PDO can close the DB cursor
            // we anyway expect either no rows, or one row with one column. fetchColumn, seems to be buggy #4777
            $sessionRows = $stmt->fetchAll(\PDO::FETCH_NUM);
            if (count($sessionRows) == 1) {
                return base64_decode($sessionRows[0][0]);
            }
            // session does not exist, create it
            $this->createNewSession($id);
            return '';
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to read the session data: %s', $e->getMessage()), 0, $e);
        }
    }
    /**
     * {@inheritDoc}
     */
    public function write($id, $data)
    {
        // get table/column
        $dbTable   = $this->dbOptions['db_table'];
        $dbDataCol = $this->dbOptions['db_data_col'];
        $dbIdCol   = $this->dbOptions['db_id_col'];
        $dbTimeCol = $this->dbOptions['db_time_col'];
        //session data can contain non binary safe characters so we need to encode it
        $encoded = base64_encode($data);
        try {
            $driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ('mysql' === $driver) {
                // MySQL would report $stmt->rowCount() = 0 on UPDATE when the data is left unchanged
                // it could result in calling createNewSession() whereas the session already exists in
                // the DB which would fail as the id is unique
                $stmt = $this->pdo->prepare(
                    "INSERT INTO $dbTable ($dbIdCol, $dbDataCol, $dbTimeCol) VALUES (:id, :data, :time) " .
                    "ON DUPLICATE KEY UPDATE $dbDataCol = VALUES($dbDataCol), $dbTimeCol = VALUES($dbTimeCol)"
                );
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
                $stmt->execute();
            } elseif ('oci' === $driver) {
                $stmt = $this->pdo->prepare("MERGE INTO $dbTable USING DUAL ON($dbIdCol = :id) ".
                    "WHEN NOT MATCHED THEN INSERT ($dbIdCol, $dbDataCol, $dbTimeCol) VALUES (:id, :data, sysdate) " .
                    "WHEN MATCHED THEN UPDATE SET $dbDataCol = :data WHERE $dbIdCol = :id");
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->execute();
            } else {
                $stmt = $this->pdo->prepare("UPDATE $dbTable SET $dbDataCol = :data, $dbTimeCol = :time WHERE $dbIdCol = :id");
                $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
                $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
                $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
                $stmt->execute();
                if (!$stmt->rowCount()) {
                    // No session exists in the database to update. This happens when we have called
                    // session_regenerate_id()
                    $this->createNewSession($id, $data);
                }
            }
        } catch (\PDOException $e) {
            throw new \RuntimeException(sprintf('PDOException was thrown when trying to write the session data: %s', $e->getMessage()), 0, $e);
        }
        return true;
    }
    /**
     * Creates a new session with the given $id and $data
     *
     * @param string $id
     * @param string $data
     *
     * @return boolean True.
     */
    private function createNewSession($id, $data = '')
    {
        // get table/column
        $dbTable   = $this->dbOptions['db_table'];
        $dbDataCol = $this->dbOptions['db_data_col'];
        $dbIdCol   = $this->dbOptions['db_id_col'];
        $dbTimeCol = $this->dbOptions['db_time_col'];
        $dbIpCol   = $this->dbOptions['db_ip_col'];
        $sql = "INSERT INTO $dbTable ($dbIdCol, $dbDataCol, $dbTimeCol, $dbIpCol) VALUES (:id, :data, :time, :ip)";
        //session data can contain non binary safe characters so we need to encode it
        $encoded = base64_encode($data);
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_STR);
        $stmt->bindParam(':data', $encoded, \PDO::PARAM_STR);
        $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
        $stmt->bindValue(':ip', PdoSessionHandler::getip(), \PDO::PARAM_STR);
        $stmt->execute();
        return true;
    }


    private static function validip($ip) {
        if (!empty($ip) && ip2long($ip)!=-1) {
            $reserved_ips = array (
                array('0.0.0.0','2.255.255.255'),
                array('10.0.0.0','10.255.255.255'),
                array('127.0.0.0','127.255.255.255'),
                array('169.254.0.0','169.254.255.255'),
                array('172.16.0.0','172.31.255.255'),
                array('192.0.2.0','192.0.2.255'),
                array('192.168.0.0','192.168.255.255'),
                array('255.255.255.0','255.255.255.255')
            );
            foreach ($reserved_ips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
            }
            return true;
        } else {
            return false;
        }
    }

    private static function getip() {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) && self::validip($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
            foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
                if (self::validip(trim($ip))) {
                    return $ip;
                }
            }
        }
        if (isset($_SERVER["HTTP_X_FORWARDED"]) && self::validip($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_FORWARDED_FOR"]) && self::validip($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (isset($_SERVER["HTTP_FORWARDED"]) && self::validip($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } elseif (isset($_SERVER["HTTP_X_FORWARDED"]) && self::validip($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } else {
            return isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "";
        }
    }
}