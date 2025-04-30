<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

//only allowing post and get requests
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
    http_response_code(405); // Method Not Allowed
    exit;
}
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
    function GET($user) {
        //check if the user is empty, if so it throws a 400 status code
        if (empty($user)) {
            http_response_code(400);
            echo json_encode(["Invalid parameters!"]);
            return false;
        }
        try {
            //trying the select queries if it fails then it throws a 500 status code in the except block, and states what the error is in json form
            $sent_stmnt = $this->conn->prepare("SELECT * FROM message WHERE source = ?");
            $sent_stmnt->bind_param("s", $user);

            $sent_stmnt->execute();
            $sent_result = $sent_stmnt->get_result();

            $recieved_stmnt = $this->conn->prepare("SELECT * FROM message WHERE target = ?");
            $recieved_stmnt->bind_param("s", $user);

            $recieved_stmnt->execute();
            $recived_result = $recieved_stmnt->get_result();

        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }

        //set up the json structure to segregate the sent by and recieved by messages
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
//            echo json_encode(["Invalid parameters! | Status Code: 204"]);

            http_response_code(204);
            return false;
        } else {
            $json_data = json_encode($messages, JSON_PRETTY_PRINT);

            file_put_contents("messages.json", $json_data, FILE_APPEND);
            echo $json_data;
            http_response_code(200);
            return true;
        }
    }

    //This is creating a message insertion into the database
    function POST($source, $target, $message) {
        //if there are incorrect parameters passed through it, it throws a 400 status code
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

            //if the message is made and has been inserted into the database then it throws a 201 status code
            if ($stmnt->affected_rows > 0) {
                $check_stmt  = $this->conn->prepare("SELECT LAST_INSERT_ID();");
                $check_stmt->execute();
                $check_results = $check_stmt->get_result();
                $id = $check_results->fetch_assoc();
                http_response_code(201);
                echo json_encode($id);
                return true;
            } else {
                //if it didnt make a database insertion then it throws a 500 status code
                http_response_code(500);
                return false;
            }
        } catch (mysqli_sql_exception $e){
            //if any of the select queries ort database transactions fail, then it throws a 500 status code
            HTTP_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }
    }
}

//creates a new db_access class and kills it if there is no connection available
$db = new db_access();
if (!$db->getConnection()) {
    http_response_code(500);
    die(json_encode(['ERROR' =>"DB connection failed!"]));
}

//creating a new messageClass and passing through the db class as a parameter
$message = new messageService($db);


//checks if the method is GET or POST and calls a certain method depending on which one it is
if ($method == 'GET') {
    $message->GET($_GET['source']);
} else {
    $message->POST($_GET['source'], $_GET['target'], $_GET['message']);
}

//$message->POST("women", "women", "This is a message");
//$message->GET("bob");

//$conn->GET("blah", "blah");

?>