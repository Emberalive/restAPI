<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

//only allowing post and get requests
//$method = $_SERVER['REQUEST_METHOD'];
//if (!in_array($method, ['GET', 'POST'])) {
//    http_response_code(405); // Method Not Allowed
//    exit;
//}
class db_access {
    private $host = "165.227.235.122";
    private $user = "ss2979_samuel";
    private $pass = "QwErTy1243!";
    private $db = "ss2979_restAPI";
    private $conn;

    //create the connection to a database
    public function __construct() {
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db);
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        return null;
    }

    //close the connection to the database
    public function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
class messageService {
    private $conn;
    function __construct(db_access $dbConnection){
        $this->conn = $dbConnection->getConnection();
    }

    //this is getting the messages sent from someone else and received by a specific user
    function GET($source, $target) {

        if (empty($source) || empty($target)) {
            http_response_code(400);
            echo json_encode(["Invalid parameters!"]);
            return false;
        }
        try {
            $sent_stmnt = $this->conn->prepare("SELECT * FROM message WHERE source = ? AND target = ?");
            $sent_stmnt->bind_param("ss", $source, $target);

            $sent_stmnt->execute();
            $sent_result = $sent_stmnt->get_result();

            $recieved_stmnt = $this->conn->prepare("SELECT * FROM message WHERE source = ? AND target = ?");
            $recieved_stmnt->bind_param("ss", $target, $source);

            $recieved_stmnt->execute();
            $recived_result = $recieved_stmnt->get_result();

        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }

        $messages = [
            "sent_from" => [],
            "received_by" => []
        ];

        while ($row = $recived_result->fetch_assoc()) {
            array_push($messages['received_by'], $row);
        }

        // Fetch sent messages
        while ($row = $sent_result->fetch_assoc()) {
            array_push($messages['sent_from'], $row);
        }

        if (empty($messages['received_by']) && empty($messages['sent_from'])) {
            // Debug: Check if headers are already sent
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in $file on line $line");
            }

            http_response_code(204);
//            echo json_encode(["Invalid parameters!"]);

            exit;
        } else {
            $json_data = json_encode($messages, JSON_PRETTY_PRINT);

            file_put_contents("messages.json", $json_data, FILE_APPEND);
            echo $json_data;
            http_response_code(200);
        }
        return true;
    }

    //This is creating a message insertion into the database
    function POST($source, $target, $message) {
        if (empty($source) || empty($target) || empty($message)) {
            http_response_code(400);
            echo json_encode(["Invalid parameters!"]);
            return false;
        }
        try{
            $stmnt = $this->conn->prepare("INSERT INTO message (target, source, text)
      VALUES(?, ?, ?)");
            $stmnt->bind_param("sss", $target, $source, $message);
            $stmnt->execute();

            if ($stmnt->affected_rows > 0) {
                $check_stmt  = $this->conn->prepare("SELECT LAST_INSERT_ID();");
                $check_stmt->execute();
                $check_results = $check_stmt->get_result();
                $id = $check_results->fetch_assoc();
                http_response_code(201);
                echo json_encode($id);
                return true;
            } else {
                http_response_code(500);
                return false;
            }
        } catch (mysqli_sql_exception $e){
            HTTP_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }
    }
}

$db = new db_access();
if (!$db->getConnection()) {
    die("DB connection failed!");
}

$message = new messageService($db);

//if ($method == 'GET') {
//    $message->GET($_GET['source'], $_GET['target']);
//} else {
//    $message->POST($_GET['source'], $_GET['target'], $_GET['message']);
//}

//$message->POST("woman", "man", "This is a message");
$message->GET("man", "women");

//$conn->GET("blah", "blah");

?>