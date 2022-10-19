<?php


class User
{
  public string $telefon;
  private int $accessTimeLimit = 120; // seconds -> 2 min
  private int $waitTimeLimit = 30; // seconds -> 0.5 min
  private int $waitMaxTimeLimit = 72000; // seconds -> 20 hour
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
    // Check User Action
    $result = $this->checkUserActions();
    if ($result !== 'ok') return $result;

    // Change table
    $this->table = 'clients';

    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':telefon', $this->telefon);
    $stmt->execute();

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userData) { // UPDATE USER VERIFICATION CODE
      $result = $this->updateUserCode($userData['id']);
      if ($result !== 'no') return $result;
    } else { // CREATE USER WITH ADD VERIFIVATION CODE
      $result = $this->createUser();
      if ($result !== 'no') return $result;
    }

    return [
      'data' => [
        'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring',
      ],
      'status_code' => 500
    ];
  }

  public function verification()
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
    if ($checkResult !== 'ok') return $checkResult;

    // Change table
    $this->table = 'client';

    return false;
  }

  // Check user actions
  private function checkUserActions()
  {
    //Change table
    $this->table = 'actions';

    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon ';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':telefon', $this->telefon);
    $stmt->execute();
    $userActionData = $stmt->fetch(PDO::FETCH_ASSOC);
    $lastAction = $userActionData['urinish'];
    $lastActionTime = $userActionData['last'];
    $userId = $userActionData['id'];


    if ($lastAction > 3) {
      $time = time() - $lastActionTime;
      // Check user wait time
      if ($lastAction > 3 && $lastAction <= 6) {
        $this->waitTimeLimit = 120; // 2 minute
      } else if ($lastAction > 6 && $lastAction <= 10) {
        $this->waitTimeLimit = 1800; // 30 minute
      } else if ($lastAction > 10) {
        $this->waitTimeLimit = $this->waitMaxTimeLimit; // 20 hour
      } else if($time > $this->waitMaxTimeLimit) { // end wait max time limit
        $result = $this->updateUserAction(0, $userId);
        if($result !== 'ok') return $result;
      }

      $liveWaitTime = $this->waitTimeLimit - $time;
      if ($liveWaitTime > 0 && $time < $this->accessTimeLimit) {
        return [
          'data' => [
            'message' => 'Urinishlar soni ko\'p! ' . $liveWaitTime . ' soniyadan so\'ng qayta urinib ko\'ring',
            'waitTime' => $liveWaitTime,
          ],
          'status_code' => 400,
        ];
      }
    }
    return 'ok';
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
    curl_exec($curl);
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

  //Create new user
  private function createUser()
  {
    $code = $this->generateCode();

    // Send message
    $result = $this->sendMessage($this->telefon, $code);

    if ($result === 'ok') {

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
        $this->table = 'actions';

        $action = 1;
        $query = 'INSERT INTO  ' . $this->table . ' SET last=:last, urinish=:action, telefon=:telefon';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':last', time());
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':telefon', $this->telefon);

        if ($stmt->execute()) {
          $resTel = $this->telefon;
          $resTel = '********'.($resTel[strlen($resTel) -2].$resTel[strlen($resTel) -1]);
          // return success message
          return [
            'data' => [
              'message' => $resTel . ' raqamingizga tasdiqlash kodini yubordik!',
            'accessTime' => $this->accessTimeLimit,
            ],
            'status_code' => 200
          ];
        }
      }
    }
    return "no";
  }

  // Update user code
  private function updateUserCode($userId)
  {
    $code = $this->generateCode();

    // Send message
    $result = $this->sendMessage($this->telefon, $code);

    if ($result === 'ok') {
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

        $userActionData = $this->getUserActionData();
        $action = $userActionData['urinish'] + 1;
        $id = $userActionData['id'];

        $this->table = 'actions';
        $result = $this->updateUserAction($action, $id);
        if ($result !== 'ok') return $result;

        $resTel = $this->telefon;
        $resTel = '********'.($resTel[strlen($resTel) -2].$resTel[strlen($resTel) -1]);
        
        // return success message
        return [
          'data' => [
            'message' => $resTel . ' raqamingizga tasdiqlash kodini yubordik!',
            'accessTime' => $this->accessTimeLimit,
          ],
          'status_code' => 200
        ];
      }
    }
    return 'no';
  }

  // Get user action data
  private function getUserActionData()
  {
    // change table
    $this->table = 'actions';

    $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';

    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':telefon', $this->telefon);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  // Generate message code
  private function generateCode()
  {
    return rand(10000, 99999);
  }
}
