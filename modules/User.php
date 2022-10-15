<?php


class User
{
  public string $telefon;
  private int $timeLimit = 3; // minute
  private int $waitTimeLimit = 30; // seconds
  private int $actionLimit = 3;// count
  private string $sendMessageToUrl = 'http://91.204.239.44/broker-api/send';

  // DB Stuff
  private string $table = 'clients';
  private $conn;


  // Construct
  public function __construct($db)
  {
    if (!$db) return null;
    $this->conn = $db;
  }

  // REGISTER
  public function checkUserActions() {
    if(!$this->conn || !$this->telefon) return null;

    //Create query
    $query = 'SELECT * FROM '. $this->table . ' WHERE telefon=:telefon';

    // Prepare statment
    $stmt = $this->conn->prepare($query);

    $stmt->bindParam(':telefon', $this->telefon);


  }

  // Send message to user phone
  private function sendMessage($telefon, $code)
  {
    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => $this->sendMessageToUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "POST",
      CURLOPT_POSTFIELDS => "{ \"messages\": [ { \"recipient\": \"$telefon\", \"message-id\": \"2016256\", \"sms\": { \"originator\": \"3700\", \"content\": { \"text\": \"Vakhidov.uz - oboylar. Kodni hech kimga aytmang. Kod: $code\" } } } ] }",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Basic c2FtZHU6eDlBYWJDTkZa",
        "Cache-Control: no-cache",
        "Content-Type: application/json",
      ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);


    if ($err) {
      return "no";
    } else {
      return "ok";
    }
  }
}
