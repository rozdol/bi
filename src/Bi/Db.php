<?php
namespace Rozdol\Db;

class Db
{
    private static $dbInstance;
    private $conn, $query, $query_result, $result, $last_query, $col_info, $debug_on = false, $show_errors = false;

    protected $table, $fields, $data, $lock_mod_data = false, $lock_sel_data = false, $locked_fields = null;

    public $errStr = '', $printOutStr = '';

    /**
     * sets connection to database
     *
     * @param string $host
     * @param string $login
     * @param string $pass
     * @param string $dbname
     * @param string $port
     */
    function __construct($host = 'localhost', $login = 'postgres', $pass = '1234', $dbname = 'postgres', $port = 5432)
    {
        $this->conn = @pg_connect("host=$host port=$port dbname=$dbname user=$login password=$pass");
        if (!$this->conn) {
            $this->conn = @pg_connect("host=$host port=$port dbname=postgres user=$login password=$pass");
            if (!$this->conn) {
                $this->conn = @pg_connect("host=$host port=$port dbname=template1 user=$login password=$pass");
                if (!$this->conn) {
                    die("No $dbname or any default database at host://$host:$port"."<br>host=$host port=$port dbname=$dbname user=$login password=$pass");
                }
            }
            echo "Setting up database $dbname ...<br>";
            $sql="CREATE DATABASE $dbname
  WITH OWNER = postgres
       ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'en_US.UTF-8'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;";

            $default_conn = @pg_connect("host=".$GLOBALS['DB']['DB_SERVER']."
                             port=".$GLOBALS['DB']['DB_PORT']."
                             dbname='postgres'
                             user=".$GLOBALS['DB']['DB_USER']."
                             password=".$GLOBALS['DB']['DB_PASS']);
            if ((!$default_conn)) {
                die("SQL:Can not create $dbname database. SQL:<br><pre>$sql</pre>");
            }

            if (!($cursor = pg_query($default_conn, $sql))) {
                die("SQL:Can not create $dbname database. SQL:<br><pre>$sql</pre>");
            }

                    $result = @pg_query($conn, $sql);
            if (!$result) {
                die("SQL:Can not create $dbname database. SQL:<br><pre>$sql</pre>");
            }

            $this->conn = @pg_connect("host=$host port=$port dbname=$dbname user=$login password=$pass");
            if (!$this->conn) {
                die("Can not create $dbname database");
            }
        }
    }
    public static function getInstance($host, $login, $pass, $dbname, $port)
    {
        if (!self::$dbInstance) {
            self::$dbInstance = new db($host, $login, $pass, $dbname, $port);
        }
        return self::$dbInstance;
    }

    function test()
    {
        return "ok";
    }

    /**
     * function to call any outside function used to write errors into logs
     *
     * @param string $err_string
     */
    function WriteError($err_string)
    {
    }

    /* ===================== PRIVATE PART ======================= */

    /**
     * sets data to be inserted or updated - cleans unwanted chars
     *
     * @param array $_table
     * @param array $_fields
     * @param array $_data
     */
    private function SetObjData($_table, $_fields, $_data)
    {
        $this->table = $_table;
        $this->fields = $_fields;
        $this->data[] = array();

        if (isset($_data[0]) and is_array($_data[0])) {
            for ($i=0; $i<count($_data); $i++) {
                foreach ($this->fields as $field) {
                    if (isset($_data[$i][$field]) and !$this->IsFieldLocked($field)) {
                        $this->data[$i][$field] = $this->Escape($_data[$i][$field]);
                    }
                }
            }
        } else {
            foreach ($this->fields as $field) {
                if (isset($_data[$field]) and !$this->IsFieldLocked($field)) {
                    $this->data[0][$field] = $this->Escape($_data[$field]);
                }
            }
        }
    }

    /**
     * finds locked fields
     *
     * @param string $field
     * @return bool
     */
    private function IsFieldLocked($field)
    {
        if (!is_array($this->locked_fields)) {
            return false;
        }
        $locked_tables = array_keys($this->locked_fields);
        $locked_fields = array_values($this->locked_fields);
        $_locked_fields = array();

        foreach ($locked_fields as $locked_field) {
            if (is_array($locked_field)) {
                foreach ($locked_field as $_locked_field) {
                    $_locked_fields[] = $_locked_field;
                }
            } else {
                $_locked_fields[] = $locked_field;
            }
        }

        if (in_array($this->table, $locked_tables) and in_array($field, $_locked_fields)) {
            return true;
        }

        return false;
    }

    /**
     * executes query
     *
     * @param string $qry
     * @return object
     */
    private function ExecQuery($qry)
    {
        return @pg_query($this->conn, $qry);
    }

    /**
     * fetches table fields
     *
     * @param object $res
     */
    private function FetchFields($res)
    {
        if (!$this->debug_on) {
            return ;
        }
        $i=0;
        $this->col_info = array();
        while ($i < @pg_num_fields($res)) {
            $this->col_info[$i]->name = @pg_field_name($res, $i);
            $this->col_info[$i]->size = @pg_field_size($res, $i);
            $this->col_info[$i]->type = @pg_field_type($res, $i);
            $i++;
        }
    }

    /**
     * fetches result into array. also gets info about fields
     *
     * @param object $res
     * @return array
     */
    private function Fetch($res, $row_no = null, $offset = null)
    {
        if (is_numeric($row_no)) {
            $limit = 1;
        } else {
            $limit = $this->GetNumRows();
        }

        if (is_numeric($offset)) {
            $c_output = PGSQL_NUM;
        } else {
            $c_output = PGSQL_ASSOC;
        }

        $this->FetchFields($res);

        $i = 0;
        $this->result = array();
        while ($rs = @pg_fetch_array($res, $row_no, $c_output) and $limit > $i) {
            $this->result[$i] = ($offset?$rs[$offset]:$rs);
            $i++;
        }
        @pg_free_result($this->conn);
        return $this->result;
    }

    /**
     * closes mysql connection
     *
     */
    private function Disconnect()
    {
        @pg_close($this->conn);
    }

    /**
     * sets error code returned by pgsql
     *
     */
    private function SetErrCode()
    {
        $connection_status = @pg_connection_status($this->conn);
        $last_error = @pg_last_error($this->conn);
        $result_error = @pg_result_error($this->conn);
        $last_notice = @pg_last_notice($this->conn);

        $_errors = array();

        if ($connection_status) {
            $_errors[] = ($connection_status?$connection_status:'');
        }
        if ($last_error) {
            $_errors[] = ($last_error?$last_error:'');
        }
        if ($result_error) {
            $_errors[] = ($result_error?$result_error:'');
        }
        if ($last_notice) {
            $_errors[] = ($last_notice?$last_notice:'');
        }

        if (count($_errors) > 0) {
            //$this->errStr .= '<div style="border:1px solid black; margin:4px; padding:4px; background-color:#FFDEAD;"><b>Query:</b> '.$this->last_query . '<br />'.implode('<br />', $_errors)."</div>";
            if ($_REQUEST[act]=='api') {
                foreach ($_errors as $_error) {
                    $lines=explode("\n", $_error);
                    $api_errors[]=$lines;
                }
                $err_string=json_encode(['error'=>'DB_error','errors'=>$api_errors]);//exit;
            } else {
                $err_string='<div class="alert alert-error"><h2>DB Error</h2><b>Query:</b> '.$this->last_query . '<br /><pre class="red">'.implode('<br />', $_errors)."</pre></div>";
            }
            $this->errStr .= $err_string;
            //$this->errStr .="<pre>".print_r($GLOBALS)."</pre>";
            $GLOBALS[no_refresh]=1;
        }
    }

    /**
     * Data dump from select query
     *
     */
    private function SetDebugDump($offset = null)
    {
        if (!$this->query_result or !$this->debug_on) {
            return;
        }

        $report = '<b>DATA:</b><br />
        <table border="0" cellpadding="5" cellspacing="1" style="background-color:#555555">
        <tr style="background-color:#eeeeee"><td nowrap valign="bottom"><font color="555599" size="2"><b>(row)</b></font></td>';

        if (is_numeric($offset)) {
            $z = 2;
            $report.= '<td nowrap align="left" valign="top"><font size="1" color="555599">'.$this->col_info[$offset]->type.' '.$this->col_info[$offset]->size.'</font><br><font size=2><b>'.$this->col_info[$offset]->name.'</b></font></td>';
        } else {
            $z = count($this->col_info);
            for ($i=0; $i < $z; $i++) {
                $report.= '<td nowrap align="left" valign="top"><font size="1" color="555599">'.$this->col_info[$i]->type.' '.$this->col_info[$i]->size.'</font><br><font size=2><b>'.$this->col_info[$i]->name.'</b></font></td>';
            }
        }

        $report .= "</tr>";

        if (is_array($this->result) and count($this->result) > 0) {
            $i=0;
            foreach ($this->result as $one_row) {
                $i++;
                $report.= '<tr bgcolor="ffffff"><td style="background-color:#eeeeee" nowrap align="middle"><font size="2" color="555599">'.$i.'</font></td>';

                if (is_array($one_row)) {
                    foreach ($one_row as $item) {
                        $report.= '<td nowrap style="background-color:#ffffff"><font size="2">'.htmlspecialchars($item).'</font></td>';
                    }
                } else {
                    $report.= '<td nowrap style="background-color:#ffffff"><font size="2">'.htmlspecialchars($one_row).'</font></td>';
                }


                $report.= "</tr>";
            }
        } else {
            $report.= '<tr bgcolor="ffffff"><td colspan="'.($z+1).'"><font size=2>No Results</font></td></tr>';
        }

        $report.= "</table>";
        $this->printOutStr .= $report;
    }

    /**
     * gathers some info about executed query
     *
     */
    private function SetQuerySummary()
    {
        if (!$this->query_result or !$this->debug_on) {
            return;
        }

        $this->printOutStr .= "<div class=\"alert alert-info\"><b>Query:</b> " .nl2br($this->last_query) . "<br />
        <b>Rows affected:</b> " .$this->GetAffRows() . "<br />
        <b>Num rows:</b> " .$this->GetNumRows() . "<br />".
        ($this->GetLastID()?"<b>Last INSERT ID:</b> " .$this->GetLastID() . "<br />":"")."</div>";
    }

    /**
     * gets dump string
     *
     * @return string
     */
    private function GetDump()
    {
        if ($this->debug_on) {
            return $this->printOutStr;
        }
        return '';
    }

    /**
     * gets errors
     *
     * @return string
     */
    private function GetErr()
    {
        if ($this->show_errors) {
            return nl2br($this->errStr);
        }
        return '';
    }

    /* ===================== PUBLIC PART ======================= */

    /**
     * turns debug on
     *
     */
    final function DebugOn()
    {
        $this->debug_on = true;
        $this->show_errors = true;
    }

    /**
     * turns debug off
     *
     */
    final function DebugOff()
    {
        $this->debug_on = false;
        $this->show_errors = false;
    }

    /**
     * sets flag to show errors
     *
     */
    final function ShowErrors()
    {
        $this->show_errors = true;
    }

    /**
     * runs query - wrapper for ExecQuery
     *
     * @param string $qry
     * @return int
     */
    final function Query($qry)
    {
        $GLOBALS['qry_count']++;
        $GLOBALS['qry'].=$GLOBALS['qry_count'].':'.$qry."\n";

        $this->last_query = $qry;

        $this->query_result = $this->ExecQuery($qry);

        if ($this->query_result) {
            $this->SetQuerySummary();
            return $this->query_result;
        }

        $this->SetErrCode();
        if (!$this->show_errors) {
            $this->WriteError($this->GetErr());
        }

        return false;
    }
    /**
     * gets affected rows
     *
     * @return int
     */
    final function GetAffRows()
    {
        return @pg_affected_rows($this->query_result);
    }

    /**
     * gets no of rows
     *
     * @return int
     */
    final function GetNumRows()
    {
        return @pg_num_rows($this->query_result);
    }

    /**
     * gets last inserted id
     *
     * @return int
     */
    final function GetLastID($offset = 0, $seq_suffix = 'seq')
    {
        $regs = array();
        preg_match("/insert\\s*into\\s*\"?(\\w*)\"?/i", $this->last_query, $regs);

        if (count($regs) > 1) {
            $table_name = $regs[1];
            $res = @pg_query($this->conn, "SELECT * FROM $table_name WHERE 1 != 1");
            $query_for_id = "SELECT CURRVAL('{$table_name}_".@pg_field_name($res, $offset)."_{$seq_suffix}'::regclass)";
            $result_for_id = @pg_query($this->conn, $query_for_id);

            $last_id = @pg_fetch_array($result_for_id, 0, PGSQL_NUM);
            return $last_id[0];
        }
        return null;
    }

    /**
     * gets results from select query
     *
     * @param string $qry
     * @return array
     */
    final function GetResults($qry)
    {
        if ($this->lock_sel_data) {
            return array();
        }
        if ($this->Query($qry)) {
            $this->result = $this->Fetch($this->query_result);
            $this->SetDebugDump();

            return $this->result;
        }
        return array();
    }



    /**
     * gets one row from a table
     *
     * @param string $qry
     * @return array
     */
    final function GetRow($qry)
    {
        if ($this->lock_sel_data) {
            return array();
        }
        if ($this->Query($qry)) {
            $this->result = $this->Fetch($this->query_result, 0);
            $this->SetDebugDump();

            return $this->result[0];
        }
        return array();
    }

    /**
     * gets one col from a table
     *
     * @param string $qry
     * @param int $offset
     * @return array
     */
    final function GetCol($qry, $offset = 0)
    {
        if ($this->lock_sel_data) {
            return array();
        }
        if ($this->Query($qry)) {
            $this->result = $this->Fetch($this->query_result, null, $offset);
            $this->SetDebugDump($offset);

            return $this->result[0];
        }
        return array();
    }

    /**
     * gets one var from a table
     *
     * @param string $qry
     * @param int $row_no
     * @param int $offset
     * @return string - the var
     */
    final function GetVar($qry, $row_no = 0, $offset = 0)
    {
        if ($this->lock_sel_data) {
            return array();
        }
        if ($this->Query($qry)) {
            $this->result = $this->Fetch($this->query_result, $row_no, $offset);
            $this->SetDebugDump($offset);

            return $this->result[0][0];
        }
        return array();
    }
    final function GetVal($qry, $row_no = 0, $offset = 0, $debug = 0)
    {
        if ($debug>0) {
            echo "$qry<br>";
        }
        if ($this->lock_sel_data) {
            return "Row is locked";
        }
        if ($this->Query($qry)) {
            $this->result = $this->Fetch($this->query_result, $row_no, $offset);
            $this->SetDebugDump($offset);

            $res = $this->result[0][0];
            return $res;
        }
        return "No query given";
    }

    /**
     * inserts rows
     *
     * @return int
     */
    function InsertObject($table, $fields, $data)
    {
        if ($this->lock_mod_data) {
            return 0;
        }

        $this->SetObjData($table, $fields, $data);
        $query = '';

        for ($i=0; $i<count($this->data); $i++) {
            if (!is_array($this->data[$i])) {
                continue;
            }
            $insert_fields = array_keys($this->data[$i]);
            $insert_values = array_values($this->data[$i]);

            if (count($insert_fields) == count($insert_values) and count($insert_fields) > 0) {
                $query .= "INSERT INTO $this->table (".implode(',', $insert_fields).") VALUES ('".implode("','", $insert_values)."');\n";
            }
        }

        unset($this->data);
        unset($this->fields);
        unset($this->table);

        if ($query) {
            return $this->Query($query);
        } else {
            return 0;
        }
    }

    /**
     * updates row
     *
     * @param string $where_part
     * @return int
     */
    function UpdateObject($where_part, $table, $fields, $data)
    {
        if (!$where_part or $this->lock_mod_data) {
            return 0;
        }

        $this->SetObjData($table, $fields, $data);

        $query = '';
        $set_part = array();
        $update_objects = $this->data[0];

        foreach ($update_objects as $update_field => $update_value) {
            $set_part[] = "$update_field = '$update_value'";
        }

        if (count($set_part) > 0) {
            $query .= "UPDATE $this->table SET ".implode(', ', $set_part)." WHERE $where_part;";
        }

        unset($this->data);
        unset($this->fields);
        unset($this->table);

        if ($query) {
            return $this->Query($query);
        } else {
            return 0;
        }
    }

    /**
     * deletes rows
     *
     * @param string $where_part
     * @return int
     */
    function DeleteObject($where_part, $table)
    {
        if (!$where_part or !$table or $this->lock_mod_data) {
            return 0;
        }

        $query = "DELETE FROM $table WHERE ".$where_part;

        return $this->Query($query);
    }

    /**
     * data modification lock switch
     *
     * @param bool $lock
     */
    function LockModData($lock = true)
    {
        $this->lock_mod_data = $lock;
    }

    /**
     * selects lock switch
     *
     * @param bool $lock
     */
    function LockSelData($lock = true)
    {
        $this->lock_sel_data = $lock;
    }

    /**
     * locks table fields for insert or update
     *
     * @param array $fields - array('table'=>'field')
     */
    function LockTableFields($fields)
    {
        $this->locked_fields = $fields;
    }

    /**
     * same as join, but wont allow empty vals and escapes values for safe use in query
     *
     * @param string $separator
     * @param array $array
     * @return string
     */
    function JoinNotEmpty($separator, $array)
    {
        if (!is_array($array)) {
            return '';
        }

        $rv = trim(array_shift($array));

        foreach ($array as $item) {
            $item = $this->Escape(trim($item));
            if ($rv != '' and $item != '') {
                $rv .= $separator;
            }
            $rv .= $item ;
        }
        return $rv;
    }

    /**
     * sets SQL statement for IN items
     *
     * @param various $items
     * @return string
     */
    function IN($items)
    {
        $comma_separated_items = $this->JoinNotEmpty("','", is_array($items) ? $items : explode(',', $items));
        $count_items = substr_count($comma_separated_items, ',') + 1;

        if (trim($comma_separated_items) == '') {
            $count_items = 0;
        }

        if ($count_items > 1) {
            return " IN ('$comma_separated_items') ";
        } elseif ($count_items == 1) {
            return " = '$comma_separated_items' " ;
        } else {
            return ' IS NULL ' ;
        }
    }

    /**
     * sets SQL statements for NOT IN items
     *
     * @param various $items
     * @return string
     */
    function NOT_IN($items)
    {
        $comma_separated_items = $this->JoinNotEmpty("','", is_array($items) ? $items : explode(',', $items));
        $count_items = substr_count($comma_separated_items, ',') + 1;

        if (trim($comma_separated_items) == '') {
            $count_items = 0;
        }

        if ($count_items > 1) {
            return " NOT IN ('$comma_separated_items') ";
        } elseif ($count_items == 1) {
            return " != '$comma_separated_items' ";
        } else {
            return ' IS NOT NULL ' ;
        }
    }

    private function array_sql_ins($array1)
    {
        $res="";
        $delim=", ";
        foreach ($array1 as $key1 => $value1) {
            //echo "$key1 => $value1<br>";
            $value2=pg_escape_string($value1);
            $value2=str_ireplace("''''", "''", $value2);
            if ($value2=='null') {
                $vars.="$key1$delim";
                $vals.="null$delim";
            } else {
                $vars.="$key1$delim";
                $vals.="'$value2'$delim";
            }

              //foreach ($value1 as $key => $value) {echo "$key => $value<br>";$res.="$value$delim"; }
        }

        $dellen=strlen($delim)*-1;
        $vars=substr($vars, 0, $dellen);
        $vals=substr($vals, 0, $dellen);
        $res="($vars) VALUES ($vals)";
        return $res;
    }
    private function array_sql_upd($array1)
    {
        $res="";
        $delim=", ";
        foreach ($array1 as $key1 => $value1) {
            //echo "$key1 => $value1<br>";
            $value2=pg_escape_string($value1);
            $value2=str_ireplace("''''", "''", $value2);
            if ($value2=='null') {
                $vars.="$key1=null$delim";
            } else {
                $vars.="$key1='{$value2}'$delim";
            }
            //$vars.="$key1='{$value2}'$delim";
              //foreach ($value1 as $key => $value) {echo "$key => $value<br>";$res.="$value$delim"; }
        }

        $dellen=strlen($delim)*-1;
        $vars=substr($vars, 0, $dellen);
        $res="$vars";
        return $res;
    }

    public function insert_db($table = '', $vals = [], $noid = '')
    {
        $vars=$this->array_sql_ins($vals);
        $sql="INSERT INTO $table $vars;";
        $err=$this->GetVal($sql);
        if ($err!='') {
            echo $err;
            exit;
        }
        if ($noid=='') {
            $id=($this->GetVal("select max(id) from $table")*1);
        }
        return $id;
    }
    public function update_db($table = '', $id = 0, $vals = [])
    {
        $vars=$this->array_sql_upd($vals);
        $sql="update $table set $vars where id=$id;";
        $err=$this->GetVal($sql);
        if ($err!='') {
            echo $err;
            exit;
        }
        return $id;
    }
    public function delete_db($table, $id)
    {
        $sql="delete from $table where id=$id;";
        $err=$this->GetVal($sql);
        if ($err!='') {
            echo $err;
            exit;
        }
        return $id;
    }


    /**
     * escapes string for safe use in a query
     *
     * @param string $str
     * @return string
     */
    function Escape($str)
    {
        return pg_escape_string($str);
    }

    /**
     * makes some cleanup and shows errors and debug info with dump
     *
     */
    function __destruct()
    {
        $this->Disconnect();
        echo $this->GetErr();
        echo $this->GetDump();
    }
}
