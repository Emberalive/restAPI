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
class DBAaccess {
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

    public function get_connection() {
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
class MessageService {
    private $conn;
    function __construct(DBAaccess $dbConnection){
        $this->conn = $dbConnection->getConnection();
    }

    //this is getting the messages sent from someone else and received by a specific user
    function GET($source, $target) {
        //check if the user is empty, if so it throws a 400 status code
        if (empty($source) && empty($target)) {
            http_response_code(400);
            echo json_encode(["error" => "At least one of source or target must be provided."]);
            return false;
        } else if ($target == $source) {
            http_response_code(400);
            echo json_encode(["error" => "Both parameters are the same user"]);
            return false;
        }

        try {
            $messages = [];

            if (!empty($source) && !empty($target)) {
                // source and target both present
                $stmt = $this->conn->prepare("SELECT * FROM message WHERE source = ? AND target = ?");
                $stmt->bind_param("ss", $source, $target);
                $stmt->execute();
                $result = $stmt->get_result();
                $messages["messages"] = $result->fetch_all(MYSQLI_ASSOC);
            } else if (!empty($source)) {
                // only source
                $stmt = $this->conn->prepare("SELECT * FROM message WHERE source = ?");
                $stmt->bind_param("s", $source);
                $stmt->execute();
                $result = $stmt->get_result();
                $messages["messages"] = $result->fetch_all(MYSQLI_ASSOC);
            } else if (!empty($target)) {
                // only target
                $stmt = $this->conn->prepare("SELECT * FROM message WHERE target = ?");
                $stmt->bind_param("s", $target);
                $stmt->execute();
                $result = $stmt->get_result();
                $messages["messages"] = $result->fetch_all(MYSQLI_ASSOC);
            }

            if (empty($messages["messages"])) {
                http_response_code(204);
            } else {
                http_response_code(200);
                echo json_encode($messages, JSON_PRETTY_PRINT);
            }
            return true;
        } catch (mysqli_sql_exception $e) {
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
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
            http_response_code(500);
            echo json_encode(["error" => $e->getMessage()]);
            return false;
        }
    }
}

//creates a new db_access class and kills it if there is no connection available
$db = new DBAaccess();
if (!$db->get_connection()) {
    http_response_code(500);
    die(json_encode(['ERROR' =>"DB connection failed!"]));
}

//creating a new messageClass and passing through the db class as a parameter
$message = new MessageService($db);


//checks if the method is GET or POST and calls a certain method depending on which one it is
if ($method == 'GET') {
    $source = isset($_GET['source']) ? $_GET['source'] : null;
    $target = isset($_GET['target']) ? $_GET['target'] : null;
    $message->GET($source, $target);
} else {
    $message->POST($_GET['source'], $_GET['target'], $_GET['message']);
}
?>