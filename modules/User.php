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
    if (!$this->conn) {
      return [
        'data' => [
          'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring'
        ],
        'status_code' => 500,
      ];
    }

    // change table
    $this->table = 'actions';

    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':telefon', $this->telefon);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($userData) {
      $userId = $userData['id'];
      $lastAction = $userData['urinish'];
      $lastActionTime = $userData['last'];

      $time = time() - $lastActionTime;
      $waitTime = $this->waitTimeLimit * $lastAction - $time;
      if ($waitTime > 0) { // wait user
        return [
          'data' => [
            'waitTime' => $waitTime,
          ],
          'status_code' => 400
        ];
      } else if ($time > 72000) {
        $actions = 0;
        $updateResult = $this->updateUserAction($actions, $userId);
        if ($updateResult !== 'ok') return $updateResult;
      }
    }
    return 'ok';
  }

  public function register()
  {
    // Check DB Connection
    if (!$this->conn) {
      return [
        'data' => [
          'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring'
        ],
        'status_code' => 500,
      ];
    }

    // Check User Actions
    $checkResult = $this->checkUserActions();
    if ($checkResult !== 'ok') {
      return $checkResult;
    }

    // Change table
    $this->table = 'clients';

    // Create query
    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';

    // Prepare statment
    $stmt = $this->conn->prepare($query);

    // Bind data
    $stmt->bindParam(':telefon', $this->telefon);

    // Execute query
    $stmt->execute();

    // Fetch data
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);


    //Check user data
    if ($userData) { // update token

      // User userData
      $userId = $userData['id'];

      $code = $this->generateCode();
      
      $result = $this->sendMessage($this->telefon, $code);

      if ($result == 'ok') {
        // Create query
        $query = 'UPDATE ' . $this->table . ' SET code=:code WHERE id=:userId';

        // Prepare statment
        $stmt = $this->conn->prepare($query);

        // Convert code to md5
        $code = md5($code);

        // Bind data
        $stmt->bindParam(':code', $code);
        $stmt->bindParam(':userId', $userId);

        // Execute query
        if ($stmt->execute()) {
          // return success message
          return [
            'data' => [
              'message' => $this->telefon,
            ],
            'status_code' => 200
          ];
        }
      }
    } else { // add new user with generate token
      $code = $this->generateCode();

      // $result = $this->sendMessage($this->telefon, $code);
      $result = 'ok';
      if ($result == 'ok') {

        // Create query
        $query = 'INSERT INTO ' . $this->table . ' SET telefon=:telefon, code=:code, fio="", korxona="", region="", manzil="", shaxs_turi="", qarz=0, seriya="", balans=0, sana=0, lastfoiz=0, token="", parol="", last=0 ';

        // Prepare statment
        $stmt = $this->conn->prepare($query);

        // Convert code to md5
        $code = md5($code);

        // Bind data
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':code', $code);

        // Execute query with check
        if ($stmt->execute()) {
          // return success message
          return [
            'data' => [
              'message' => $this->telefon,
            ],
            'status_code' => 200
          ];
        }
      }
    }
    // Return server error message
    return [
      'data' => [
        'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring'
      ],
      'status_code' => 500,
    ];
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
    return [
      'data' => [
        'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring',
        'erorr: ' . $stmt->error
      ],
      'status_code' => 500
    ];
  }

  // Generate message code
  private function generateCode()
  {
    return rand(10000, 99999);
  }
}
