<?php

class Profile extends User
{
    public $token;

    public $fio;
    public $location;
    public $article;
    public $count;
    public $cashback;

    // Create orider
    public function createOrder()
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

        $this->table = 'order_clients';
        $time = time();

        $query = 'INSERT INTO ' . $this->table . ' SET article=:article, phone=:phone, soni=:count, manzil=:location, sana=:date, status="", cashback=:cashback';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article', $this->article);
        $stmt->bindParam(':phone', $this->telefon);
        $stmt->bindParam(':count', $this->count);
        $stmt->bindParam(':location', $this->location);
        $stmt->bindParam(':date', $time);
        $stmt->bindParam(':cashback', $this->cashback);

        if (!$stmt->execute()) {
            return [
                'data' => [
                    'message' => 'Ichki xatolik!'
                ],
                'status_code' => 500
            ];
            exit;
        }
        $num = $this->telefon;
        $num = '********'.($num[strlen($num) - 3] . $num[strlen($num) - 2] . $num[strlen($num) - 1]);
        return [
            'data' => [
                'message' => 'Buyurtma qabul qilindi! Siz bilan '.$num.' raqamingiz orqali bog\'lanamiz!'
            ],
            'status_code' => 200
        ];
        exit;
    }

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
            return [
                'data' => [
                    'message' => 'Ma\'lumot topilmadi!.'
                ],
                'status_code' => 404
            ];
        }


        return [
            'data' => [
                'message' => 'Bad request.'
            ],
            'status_code' => 400
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
            $userBalans = $userData['balans'];
            return [
                'data' => [
                    'balans' => $userBalans
                ],
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
                'message' => 'Ma\'lumot topilmadi.',
            ],
            'status_code' => 404
        ];
    }

    // Update user data
    public function updateUserData()
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
            $result = $this->updateUserDataQuery();

            if ($result !== 'ok') return $result;
            return [
                'data' => [
                    'telefon' => $userData['telefon'],
                    'fio' => $this->fio,
                ],
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

    // Access Code
    public function accessCode()
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
        $result = $this->checkUserActions(3);
        if ($result !== 'ok') return $result;

        $userData = $this->getUserData($this->token);
        if ($userData) {

            $userId = $userData['id'];
            $this->telefon = $userData['telefon'];

            // Get user data from actions
            $userActionData = $this->getUserActionData();
            $action = $userActionData['urinish'] + 1;
            $actionUserId = $userActionData['id'];

            //Update user action
            $result = $this->updateUserAction($action, $actionUserId);
            if ($result !== 'ok') return $result;

            $result = $this->updateUserCode($userId);
            if ($result != 'no') return $result;

            return [
                'data' => [
                    'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring.',
                ],
                'status_code' => 500
            ];
        }

        return [
            'data' => [
                'message' => 'Ma\'lumot topilmadi.',
            ],
            'status_code' => 404
        ];
    }

    private function updateUserDataQuery()
    {
        $this->table = 'clients';
        $query = 'UPDATE ' . $this->table . ' SET fio=:fio, telefon=:telefon WHERE token=:token';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':fio', $this->fio);
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':token', $this->token);
        if (!$stmt->execute()) {
            return [
                'data' => [
                    'erorr' => 'Error: ' . $stmt->error()
                ],
                'status_code' => 500
            ];
        }
        return 'ok';
    }
}
