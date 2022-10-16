<?php


class User
{
  public string $telefon;
  private int $timeLimit = 3; // minute
  private int $waitTimeLimit = 30; // seconds
  private int $waitMaxTimeLimit = 72000; // day
  private int $actionLimit = 3; // count
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
  public function checkUserActions()
  {
    if (!$this->conn || !$this->telefon) return null;

    // Change table 
    $this->table = 'actions';

    //Create query
    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';

    // Prepare statment
    $stmt = $this->conn->prepare($query);

    // Bind data
    $stmt->bindParam(':telefon', $this->telefon);

    // Execute query
    $stmt->execute();

    //Fetch user data
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) {
      // Get actions with last action time from user data
      $userId = $userData['id'];
      $actions = $userData['urinish'];
      $actionLastTime = $userData['last'];

      // if($actions > $this->actionLimit) {
      if ($actions > $this->actionLimit) {

        $time = (time() - $actionLastTime);
        $waitTime = ($this->waitTimeLimit * ($actions - 2)) - $time;

        if ($time > 72000) {
          // set initial actions
          $actions = 1;

          $result = $this->updateUserAction($actions, $userId);
          if ($result === 'ok') return 'ok';
          else {
            $res = [
              'data' => ['erorr' => $result],
              'status_code' => 500
            ];
            return $res;
          }
        } else if ($waitTime > 0) { // stop attemp
          $result = [
            'data'  => ['waitTime' => $waitTime],
            'status_code' => 400,
          ];
          return $result;
        }
        // Increment actions
        $actions++;

        $result = $this->updateUserAction($actions, $userId);
        if ($result === 'ok') return 'ok';
        else {
          $res = [
            'data' => ['erorr' => $result],
            'status_code' => 500
          ];
          return $res;
        }
      } else {
        // Increment actions
        $actions++;
        $result = $this->updateUserAction($actions, $userId);

        if ($result === 'ok') return 'ok';
        $res = [
          'data' => ['message' => $result],
          'status_code' => 500,
        ];
        return $res;
      }
    } else { // FOR NEW USER
      $action = 1;
      // Create query
      $query = 'INSERT INTO ' . $this->table . ' SET telefon=:telefon, urinish=:action, last=:last';

      // Prepare statment
      $stmt = $this->conn->prepare($query);

      // Bind data
      $stmt->bindParam(':telefon', $this->telefon);
      $stmt->bindParam(':action', $action);
      $stmt->bindParam(':last', time());

      // Execute query
      if ($stmt->execute()) return 'ok';

      return 'no';
    }
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
    $result = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);


    if ($err) {
      return "no";
    } else {
      return "ok";
    }
  }

  // Update user actions
  private function updateUserAction($actions, $userId)
  {
    // Create query
    $query = 'UPDATE ' . $this->table . ' SET urinish=:actions, last=:last WHERE id=:id';

    // Prepare statment
    $stmt = $this->conn->prepare($query);

    // Bind data
    $stmt->bindParam(':actions', $actions);
    $stmt->bindParam(':last', time());
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

    // Execute query
    if ($stmt->execute()) return 'ok';
    return 'Erorr: ' . $stmt->error;
  }
}
