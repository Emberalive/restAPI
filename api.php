<?php
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
            printf("Connection failed: %s\n", $this->conn->connect_error);
        } else {
            printf("Connected successfully: %s\n", $this->conn->host_info);
        }
    }

    function __destruct()
    {
        if ($this->conn) {
            $this->conn->close();
            printf("Connection closed: %s\n", $this->conn->connect_error);
            if ($this->conn->connect_error) {
                print("Connection could not be closed: " . $this->conn->connect_error);
            }
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


//        $num_rows = $sent_result->num_rows + $recived_result->num_rows;
        $messages = [];

        while ($row = $recived_result->fetch_assoc()) {
            array_push($messages, $row);
        }

        // Fetch sent messages
        while ($row = $sent_result->fetch_assoc()) {
            array_push($messages, $row);
        }

        if (count($messages) > 0) {
            $json_data = [];

            // Encode each message to JSON
            foreach ($messages as $message) {
                // Store the JSON-encoded message
                $json_data[] = json_encode($message);
            }
            file_put_contents("messages.json", implode(PHP_EOL, $json_data));
        } else {
            echo "No messages found";
        }
    }
        //This is creating a message insertion into the database
        function POST($source, $target, $message)
        {
            $stmnt = $this->conn->prepare("INSERT INTO message(target, source, message)
          VALUES(?, ?, ?)");
            $stmnt->bind_param("sss", $target, $source, $message);
            $stmnt->execute();

            $result = $stmnt->get_result();
            for ($i = 0; $i < $result->num_rows; $i++) {
                $row = $result->fetch_assoc();
            }
        }
    }

$conn = new db_access();


$conn->POST("name", "name", "This is a message");

$conn->GET("name", "name");

?>