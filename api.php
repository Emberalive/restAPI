<?php
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


    function isValidPattern($param) {
        if ($param === "" || preg_match("/^[A-Za-z0-9]{4,16}$/", $param)) {
            return false;
        }
        return true;
    }
    // Retrieve messages based on the source and/or target.
    // - If both source and target are provided, fetch messages between them.
    // - If only source is provided, fetch messages sent by the source.
    // - If only target is provided, fetch messages received by the target.
    // Return a 400 error if neither source or target is provided.
    function GET() {
        try {
            if (!isset($_GET["source"])) {
                $source = "";
            } else {
                $source = $_GET['source'];
            }
            if (!isset($_GET["target"])) {
                $target = "";
            } else {
                $target = $_GET['target'];
        }

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
            exit;
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
            }
        } catch (mysqli_sql_exception $e){
            //if any of the select queries or database transactions fail, then it throws a 500 status code
            http_response_code(500);
            exit;
        }
    }
}

class Main {

    function __construct(MessageService $messages) {

        // Entry point of the script
        $this->messages = $messages;

        header('Content-Type: application/json');

        //only allowing post and get requests
        $method = $_SERVER['REQUEST_METHOD'];
        if (!in_array($method, ['GET', 'POST'])) {
            http_response_code(405); // Method Not Allowed
            exit;
        }

        // Route the request based on the HTTP method.
        if ($method == 'GET') {
            //handle GET request
            if (!isset($_GET['target'])) {
                $target = "";
            } else {
                $target = $_GET['target'];
            }
            if (!isset($_GET['source'])) {
                $source = "";
            } else {
                $source = $_GET['source'];
            }

            if ((empty($source) && empty($target) || $source == $target) || ($messages->isValidPattern($source) || $messages->isValidPattern($target))) {
                http_response_code(400);
            } else {
                $messages->GET();
            }
        } else {
            if (empty($_POST['source']) || empty($_POST['target']) || empty($_POST['message']) || ($_POST['source'] == $_POST['target'] ) || ($messages->isValidPattern($_POST['target']) || $messages->isValidPattern($_POST['source']))) {
                http_response_code(400);
            } else {
                $messages->POST();
            }
        }
    }
}
$db = new DBAccess();
$messages = new MessageService($db);
$main = new Main($messages);

?>