<?php
class MySessionHandler implements SessionHandlerInterface
{
    private $savePath;

    public function open($savePath, $sessionName)
    {
        echo "SP:$this->savePath SN:$sessionName<br>";
        $this->savePath = $savePath;
        if (!is_dir($this->savePath)) {
            mkdir($this->savePath, 0777);
        }

        return true;
    }

    public function close()
    {
        return true;
    }

    public function read($id)
    {
        return (string)@file_get_contents("$this->savePath/sess_$id");
    }

    public function write($id, $data)
    {
        echo "Write:id:$id Data:$data<br>";
        return file_put_contents("$this->savePath/sess_$id", $data) === false ? false : true;
    }

    public function destroy($id)
    {
        $file = "$this->savePath/sess_$id";
        if (file_exists($file)) {
            unlink($file);
        }

        return true;
    }

    public function gc($maxlifetime)
    {
        foreach (glob("$this->savePath/sess_*") as $file) {
            if (filemtime($file) + $maxlifetime < time() && file_exists($file)) {
                unlink($file);
            }
        }

        return true;
    }
}

$handler = new MySessionHandler();
if(!session_set_save_handler(
    array($handler, "open"),
    array($handler, "close"),
    array($handler, "read"),
    array($handler, "write"),
    array($handler, "destroy"),
    array($handler, "gc")
)){
     echo "Cannot initiate custom Sessions<br>";
}
register_shutdown_function('session_write_close');
session_set_save_handler($handler, true);
session_start();