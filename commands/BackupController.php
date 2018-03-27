<?php
namespace macklus\backup\commands;

use Yii;
use yii\console\Controller;
use ZipArchive;

class BackupController extends Controller
{

    private $tmp_dir;
    private $dest_dir;
    private $dbs = [];
    public $binary = [];
    public $debug = false;
    private $hasChanges = false;

    public function actionIndex()
    {
        $this->_run();
    }

    public function actionRun()
    {
        $this->_run();
    }

    private function _run()
    {
        $this->_message("Start incremental backup:\n");

        $this->_ensureFolders();

        foreach ($this->dbs as $db) {
            $obj = Yii::$app->{$db};

            // Get connection data
            $this->_message(" - $db:\n");
            $dbdata = $this->_getDbData($obj);

            foreach ($obj->schema->tableNames as $table) {
                // Dump table
                $this->_message("   - $table...Dump...");
                if ($this->_dumpTableTo($table, $table . '.sql', $dbdata)) {
                    $this->_message("[OK]...");
                } else {
                    $this->_message("[ERROR]\n");
                    continue;
                }

                // Compress file
                $this->_message("Compress...");
                if ($this->_compressFile($table . '.sql')) {
                    $this->_message("[OK]...");
                } else {
                    $this->_message("[ERROR]\n");
                    continue;
                }

                // Compare md5sum
                if (file_exists($this->dest_dir . '/' . $table . '.sql.gz')) {
                    $this->_message("MD5 test...");
                    $old_md5 = md5_file($this->dest_dir . '/' . $table . '.sql.gz');
                    $new_md5 = md5_file($this->tmp_dir . '/' . $table . '.sql.gz');
                    if ($old_md5 == $new_md5) {
                        $this->_message("SAME\n");
                    } else {
                        $this->_message("Copy...$old_md5 ... $new_md5");
                        if ($this->_copyFileFromTempToDestAndDelete($table . '.sql.gz')) {
                            $this->hasChanges = true;
                            $this->_message("[OK]\n");
                        } else {
                            $this->_message("[ERROR]\n");
                        }
                    }
                } else {
                    $this->_message("Copy...");
                    if ($this->_copyFileFromTempToDestAndDelete($table . '.sql.gz')) {
                        $this->_message("[OK]\n");
                    } else {
                        $this->_message("[ERROR]\n");
                    }
                }
            }

            //$this->gitPush();
        }
    }

    private function _copyFileFromTempToDestAndDelete($file)
    {
        if (copy($this->tmp_dir . '/' . $file, $this->dest_dir . '/' . $file)) {
            @unlink($this->tmp_dir . '/' . $file);
            return true;
        } else {
            return false;
        }
    }

    private function _compressFile($file)
    {
        if (file_exists($this->tmp_dir . '/' . $file . '.gz')) {
            @unlink($this->tmp_dir . '/' . $file . '.gz');
        }

        exec("/bin/gzip -nf " . $this->tmp_dir . '/' . $file, $output, $return_var);

        if (file_exists($this->tmp_dir . '/' . $file . '.gz')) {
            @unlink($this->tmp_dir . '/' . $file);
            return true;
        } else {
            return false;
        }
    }

    private function _dumpTableTo($table, $dest_file, $dbdata)
    {
        $dest_file = $this->tmp_dir . '/' . $dest_file;
        $command = $this->_generateDumpCommand($table, $dest_file, $dbdata);
        exec($command, $output, $return_var);
        return ($return_var == 0) ? true : false;
    }

    private function _generateDumpCommand($table, $dest_file, $dbdata)
    {
        $command = '';
        if ($dbdata['type'] == 'mysql' || $dbdata['type'] == 'mysqli') {
            $command .= 'mysqldump --skip-dump-date ';
            foreach ($dbdata as $d => $v) {
                switch ($d) {
                    case 'host':
                        $command .= " --host='$v'";
                        break;
                    case 'username':
                        $command .= " --user='$v'";
                        break;
                    case 'password':
                        $command .= " --password='$v'";
                        break;
                    case 'port':
                        $command .= " --port='$v'";
                        break;
                    case 'type':
                    case 'dbname':
                    default:
                        continue;
                }
            }
            $command .= ' ' . $dbdata['dbname'] . " $table > $dest_file";
        } else {
            throw new \macklus\backup\exceptions\UnsuportedDatabaseException("Can not create $dir directory");
        }
        return $command;
    }

    private function _getDbData($obj)
    {
        $data = [];

        foreach (explode(';', $obj->dsn) as $part) {
            //mysql:host=localhost;port=3307;dbname=testdb
            if (preg_match('/(\w+):host=(.*)/', $part, $matches)) {
                $data['type'] = $matches[1];
                $data['host'] = $matches[2];
            }
            if (preg_match('/^(\w+)=(.*)/', $part, $matches)) {
                $data[$matches[1]] = $matches[2];
            }
        }

        if (isset($obj->username)) {
            $data['username'] = $obj->username;
        }
        if (isset($obj->password)) {
            $data['password'] = $obj->password;
        }

        return $data;
    }

    private function _ensureFolders()
    {
        $this->_message(" - Directories\n");
        foreach ([$this->tmp_dir, $this->dest_dir] as $dir) {
            $this->_message("   - $dir...");
            if (!is_dir($dir)) {
                $this->_message("creating...");
                mkdir($dir, 0750, true);
            }
            if (is_dir($dir)) {
                $this->_message("[OK]\n");
            } else {
                throw new \macklus\backup\exceptions\CantCreateDirectoryException("Can not create $dir directory");
            }
        }
    }

    private function _message($msg)
    {
        if ($this->debug) {
            $this->stdout($msg);
        }
    }

    /**
     * Getters
     */
    public function setTmp_dir($value)
    {
        $this->tmp_dir = Yii::getAlias($value);
    }

    public function setDest_dir($value)
    {
        $this->dest_dir = Yii::getAlias($value);
    }

    public function setDbs($dbs)
    {
        $this->dbs = [];

        foreach ($dbs as $db) {
            if (!Yii::$app->{$db} || !Yii::$app->{$db} instanceof \yii\db\Connection) {
                continue;
            }
        }
        $this->dbs[] = $db;
    }
}
