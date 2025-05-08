<?php
// Entry point of the script
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

//only allowing post and get requests
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
   http_response_code(405); // Method Not Allowed
   exit;
}

$content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
//checking the content type of the request
if ($method == 'POST') {
    if (empty($content_type)) {
        http_response_code(400); // Bad Request
        echo json_encode(["error" => "Content-Type header is missing"]);
        exit;
    }

    if ($content_type !== 'application/x-www-form-urlencoded' && $content_type !== 'application/json') {
        http_response_code(415); // Unsupported Media Type
        echo json_encode(["error" => "Content-Type must be application/x-www-form-urlencoded or application/json"]);
        exit;
    }
}

// Establish a connection to the database.
class DBAccess {
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
            exit;
        }
    }

    // Return the database connection object.
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
    function __construct(DBAccess $dbConnection){
        $this->conn = $dbConnection->get_connection();
    }

    // Retrieve messages based on the source and/or target.
    // - If both source and target are provided, fetch messages between them.
    // - If only source is provided, fetch messages sent by the source.
    // - If only target is provided, fetch messages received by the target.
    // Return a 400 error if neither source or target is provided.
    function pattern_check($subject) {
        if (preg_match("/^[A-Za-z0-9]{4,16}$/", $subject)) {
            return true;
        }
        return false;
    }

    function GET() {
        try {
            $source = $_GET['source'];
            $target = $_GET['target'];

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
        } finally {
            //close the statement
            if (isset($stmt)) {
                $stmt->close();
            }
        }
    }
    //This is creating a message insertion into the database
    function POST() {        
        try {
            $source = $_POST['source'];
            $target = $_POST['target'];
            $message = $_POST['message'];

            $stmnt = $this->conn->prepare("INSERT INTO message (target, source, text) VALUES(?, ?, ?)");
            $stmnt->bind_param("sss", $target, $source, $message);
            $stmnt->execute();

            //if the message is made and has been inserted into the database then it throws a 201 status code
            if ($stmnt->affected_rows > 0) {
                $check_stmt = $this->conn->prepare("SELECT LAST_INSERT_ID();");
                $check_stmt->execute();
                $check_results = $check_stmt->get_result();
                $id = $check_results->fetch_row()[0];
                http_response_code(201);
                $response = [
                    "id" => $id
                ];
                echo json_encode($response, JSON_PRETTY_PRINT);
                return true;
            } else {
                //if it didnt make a database insertion then it throws a 500 status code
                http_response_code(500);
                return false;
            }
        } catch (mysqli_sql_exception $e){
            //if any of the select queries or database transactions fail, then it throws a 500 status code
            echo json_encode(["error" => $e->getMessage()]);
            http_response_code(500);
            return false;
        }
    }
}

//creates a new db_access class and kills it if there is no connection available
$db = new DBAccess();
if (!$db->get_connection()) {
    http_response_code(500);
    die(json_encode(['ERROR' =>"DB connection failed!"]));
    exit;
}

//creating a new messageClass and passing through the db class as a parameter
$message = new MessageService($db);

// Route the request based on the HTTP method.
if ($method == 'GET') {
        //handle GET request
        $source = $_GET['source'];
        $target = $_GET['target'];

        if (empty($source) && empty($target)) {
            http_response_code(400);
            echo json_encode(["error" => "At least one of source or target must be provided."]);
        } else if ($target == $source) {
            http_response_code(400);
            echo json_encode(["error" => "Both parameters are the same user"]);
        }else{
            $message->GET();
        }
} else {
    if (empty($_POST['source']) || empty($_POST['target']) || empty($_POST['message'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing field: source, target or message"]);
    } else if (!$message->pattern_check($_POST['target']) || !$message->pattern_check($_POST['source'])) {
        http_response_code(400);
        echo json_encode(["error" => "cannot contain special characters, and needs to be between 4 - 16 characters."]);
    }else if ($_POST['source'] == $_POST['target']){
        http_response_code(400);
        echo json_encode(["error" => "Both parameters are the same user"]);
    } else {
        $message->POST();
    }
}
?>