<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//
//header('Content-Type: application/json');

//$method = $_SERVER['REQUEST_METHOD'];
//if (!in_array($method, ['GET', 'POST'])) {
//    http_response_code(405); // Method Not Allowed
//    exit;
//}
class db_access
{
    private $host = "165.227.235.122";
    private $user = "ss2979_samuel";
    private $pass = "QwErTy1243!";
    private $db = "ss2979_restAPI";
    private $conn;

    function __construct()
    {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);

        if ($this->conn->connect_error) {
            HTTP_response_code(500);
        } else {
            HTTP_response_code(200);
        }
    }

    function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    //this is getting the messages sent from someone else and received by a specific user
    function GET($source, $target)
    {
        $sent_stmnt = $this->conn->prepare("SELECT * FROM message WHERE source = ? AND target = ?");
        $sent_stmnt->bind_param("ss", $source, $target);

        $sent_stmnt->execute();
        $sent_result = $sent_stmnt->get_result();

        $recieved_stmnt = $this->conn->prepare("SELECT * FROM message WHERE source = ? AND target = ?");
        $recieved_stmnt->bind_param("ss", $target, $source);

        $recieved_stmnt->execute();
        $recived_result = $recieved_stmnt->get_result();


        $messages = [];

        while ($row = $recived_result->fetch_assoc()) {
            array_push($messages, $row);
        }

        // Fetch sent messages
        while ($row = $sent_result->fetch_assoc()) {
            array_push($messages, $row);
        }

        if (count($messages) > 0) {
            $json_data = json_encode($messages, JSON_PRETTY_PRINT);

            file_put_contents("messages.json", $json_data, FILE_APPEND);
            echo $json_data;
        }
    }
        //This is creating a message insertion into the database
        function POST($source, $target, $message)
        {
            $stmnt = $this->conn->prepare("INSERT INTO message(target, source, text)
          VALUES(?, ?, ?)");
            $stmnt->bind_param("sss", $target, $source, $message);
            $stmnt->execute();

        }
    }

$conn = new db_access();

//if ($method == 'GET') {
//    $conn->GET($_GET['source'], $_GET['target']);
//} else {
//    $conn->POST($_GET['source'], $_GET['target'], $_GET['message']);
//}

$conn->POST("tittyFucker", "dickFucker", "This is a message");

$conn->GET("tittyFucker", "dickFucker");

?>