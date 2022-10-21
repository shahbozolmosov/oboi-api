<?php
class Profile extends User
{
  public $token;

  // Read orders
  public function readOrders()
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

    $userData = $this->getUserData($this->token);

    if ($userData) {

      $userId = $userData['id'];
      $userBalans = $userData['balans'];
      // Change table
      $this->table = 'order_clients';
      // Create query
      $query = 'SELECT * FROM ' . $this->table . ' WHERE client_id=:userId  ORDER BY id DESC';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
      $orderData = [];
      // Fetch data
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $newRow = [
          'id' => $row['id'],
          'article' => $row['article'],
          'phone' => $row['phone'],
          'soni' => $row['soni'],
          'manzil' => $row['manzil'],
          'sana' => date('d-m-Y', $row['sana']),
          'status' => $row['status'],
          'cashback' => $row['cashback'],
        ];
        $orderData['orders'][] = $newRow;
        $orderData['balans'] = $userBalans;
      }

      if ($orderData['orders']) {
        return [
          'data' => $orderData,
          'status_code' => 200
        ];
      }
    }



    return [
      'data' => [
        'message' => 'Ma\'lumot topilmadi.'
      ],
      'status_code' => 404
    ];
  }

  // Read cashback
  public function readCashback()
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

    $userData = $this->getUserData($this->token);

    if ($userData) {
      $userId = $userData['id'];
      // Change table
      $this->table = 'cashbackhistory';
      // Create query
      $query = 'SELECT * FROM ' . $this->table . ' WHERE client_id=:userId  ORDER BY id DESC';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
      $cashData = [];
      // Fetch data
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cashData['cashbackHistory'] = $row;
      }

      if ($cashData['cashbackHistory']) {
        return [
          'data' => $cashData,
          'status_code' => 200
        ];
      }
    }

    return [
      'data' => [
        'message' => 'Ma\'lumot topilmadi.'
      ],
      'status_code' => 404
    ];
  }

  // Read user data
  public function readUserData()
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

    $userData = $this->getUserData($this->token);

    if ($userData) {
      $resData = [
        'fio' => $userData['fio'],
        'telefon' => $userData['telefon'],
      ];
      return [
        'data' => $resData,
        'status_code' => 200
      ];
    }

    return [
      'data' => [
        'message' => 'Ma\'lumot topilmadi.'
      ],
      'status_code' => 404
    ];
  }
}
