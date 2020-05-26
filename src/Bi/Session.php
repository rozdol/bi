<?php
class MySessionHandler implements SessionHandlerInterface{
    private static $hInstance;
    public function __construct($db){

            $this->db=$db;
        // Set handler to overide SESSION
        if(!session_set_save_handler(
            array($this, "open"),
            array($this, "close"),
            array($this, "read"),
            array($this, "write"),
            array($this, "destroy"),
            array($this, "gc")
        )){
             echo "Cannot initiate custom Sessions<br>";
             return;
        }

        register_shutdown_function('session_write_close');
        session_start();
    }
    public static function getInstance($db)
    {
        if (!self::$hInstance) {
            self::$hInstance = new MySessionHandler($db);
        }
        return self::$hInstance;
    }

    /**
         * Open
         */
        public function open($savepath, $id){
            $sql="SELECT data FROM sessions WHERE id =  '$id' LIMIT 1";
            $data=$this->db->GetVal($sql);
            if($data!=""){
                // Return True
                return true;
            }
            // Return False
            return false;
        }
        /**
         * Read
         */
        public function read($id)
        {
            $sql="SELECT data FROM sessions WHERE id =  '$id' LIMIT 1";
            $data=$this->db->GetVal($sql);
            if($data!=""){
                return $data;
            } else {
                return '';
            }
        }

        /**
         * Write
         */
        public function write($id, $data)
        {
            $sql="SELECT count(*) FROM sessions WHERE id =  '$id' LIMIT 1";
            $count=$this->db->GetVal($sql)*1;
            // Create time stamp
            $access = time();
            $vals=[
                'id'=>$id,
                'access'=>$access,
                'data'=>$data,
            ];
            if($count==0){$id=$this->db->insert_db('sessions',$vals);}else{$id=$this->db->update_db('sessions',$id,$vals);}
            if ($id!='') {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Destroy
         */
        public function destroy($id)
        {
            $sql="DELETE FROM sessions WHERE id =  '$id'";
            if($this->db->GetVal($sql)){
                return true;
            }else{
                return false;
            }

        }
        /**
         * Close
         */
        public function close(){
            // Close the db connection
            // if($this->db->Disconnect()){
            //     // Return True
            //     return true;
            // }
            // // Return False
            // return false;

            return true;
        }

        /**
         * Garbage Collection
         */
        public function gc($max)
        {
            // Calculate what is to be deemed old
            $old = time() - $max;
            $sql="DELETE FROM sessions WHERE access < $old";
            $sql="select 1";
            if($this->db->GetVal($sql)){
                return true;
            }else{
                return false;
            }
        }

        public function __destruct()
        {
            $this->close();
        }
}
