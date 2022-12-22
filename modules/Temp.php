<?php

class Temp
{
    private $conn;

    /**
     * @param $db
     */
    public function __construct($db = null)
    {
        if (!$db) return null;
        $this->conn = $db;
    }

    /**
     * @param $name
     * @return array
     */
    public function writeTemp($image)
    {
        if (!$this->conn) return null;

        $this->checkAndDelTemp();

        $name = $this->generateName();
        $name = $this->base64_to_jpeg($image, $name . '.jpg');
        // CREATE QUERY
        $query = 'INSERT INTO temp_file SET name=:name, create_at=:create_at';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':create_at', time());
        if ($stmt->execute()) {
            return ['image' => 'http://oboi-api/temp/?image=' . $name, 'error' => 0];
        }
        return ['message' => 'Ichki xatolik! Qaytadan urinib ko\'ring!', 'error' => 1];
    }

    public function checkAndDelTemp()
    {
        // CREATE QUERY
        $query = 'SELECT id, name FROM temp_file WHERE ('.time().'-create_at)>60';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
            // CREATE QUERY
            $query = 'DELETE FROM temp_file WHERE id=:id';
            $delStmt = $this->conn->prepare($query);
            $delStmt->bindParam(':id', $row['id']);
            $delStmt->execute();
            unlink('../temp/'.$row['name']);
        }
    }

    private function base64_to_jpeg($base64_string, $output_file)
    {
        $folderPath = '../temp';
        if (!file_exists($folderPath)) {
            mkdir($folderPath);
        }
        $ifp = fopen($folderPath . DIRECTORY_SEPARATOR . $output_file, "wb");
        fwrite($ifp, base64_decode($base64_string));
        fclose($ifp);
        return ($output_file);
    }

    private function generateName()
    {
        $newName = time() . rand(1000, 9999);
        return md5($newName);
    }
}