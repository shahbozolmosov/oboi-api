<?php
class Database
{
    // DB Params
    private string $host = 'localhost';
    private string $db_name = 'oboiapi';
    private string $username = 'mysql';
    private string $password = 'mysql';

    // DB Connect
    public function connect()
    {
        $conn = '';

        try {
            $conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo 'Connection error ' . $e->getMessage();
        }

        return $conn;
    }

    public function filter($item)
    {
        $item = trim($item);
        $item = htmlspecialchars($item, ENT_QUOTES);
        $item = str_replace("'", "\'", $item);
        return $item;
    }

    public function filterPhoneNumber($num)
    {
        $num = $this->filter($num);
        $search = ['+','-','(',')'];
        $num = str_replace($search, '',$num);
        return $num;
    }
}
