<?php
class db_access {
    private  $host = "165.227.235.122";
    private  $user = "ss2979_samuel";
    private  $pass = "QwErTy1243!";
    private  $db = "ss2979_restAPI";
    private  $conn;
    function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);

        if ($this->conn->connect_error) {
            printf("Connection failed: %s\n", $this->conn->connect_error);
        } else {
            printf("Connected successfully: %s\n", $this->conn->host_info);
        }
    }
    function __destruct() {
        if ($this->conn) {
            $this->conn->close();
            printf("Connection closed: %s\n", $this->conn->connect_error);
        } else {
            print("Connection could not be closed: " . $this->conn->connect_error);
        }
    }
}

$conn = new db_access()

?>