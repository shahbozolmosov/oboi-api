<?php
class Profile extends User
{
  public $token;

  private $conn;
  private $table = 'order_clients';

  public function __construct($db)
  {
    if (!$db) return null;
    $this->conn = $db;
  }

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

    // Change table
    $this->table = 'clients';

    $query = 'SELECT id, balans FROM ' . $this->table . ' WHERE token=:token';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':token', $this->token);
    $stmt->execute();

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $userData['id'];
    $userBalans = $userData['balans'];
    
    if ($userData) {
      // Change table
      $this->table = 'order_clients';
      $query = 'SELECT * FROM order_clients WHERE client_id=:userId  ORDER BY id DESC';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
      $orderData = [];
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
      
      if($orderData['orders']) {
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
}
