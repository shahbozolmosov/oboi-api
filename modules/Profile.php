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

    $query = 'SELECT id FROM ' . $this->table . ' WHERE token=:token';
    $stmt = $this->conn->prepare($query);
    $stmt->bindParam(':token', $this->token);
    $stmt->execute();

    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $userData['id'];

    if ($userData) {
      // Change table
      $this->table = 'order_clients';
      $query = 'SELECT * FROM order_clients WHERE client_id=:userId';
      $stmt = $this->conn->prepare($query);
      $stmt->bindParam(':userId', $userId);
      $stmt->execute();
      $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
      if($orderData) {
        return [
          'data' => [
            'orders' => $orderData
          ],
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
