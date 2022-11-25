<?php


class User
{
    public $telefon;
    public $code;

    protected $accessTimeLimit = 120; // seconds -> 2 min
    private $waitTimeLimit = 30; // seconds -> 0.5 min
    private $waitMaxTimeLimit = 72000; // seconds -> 20 hour
    private $maxLimitAction = 10;
    private $messageUrl = 'http://91.204.239.44/broker-api/send';

    // DB Stuff
    protected $table = 'clients';

    protected $conn;


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
        $result = $this->checkUserActions(3);
        if ($result !== 'ok') return $result;

        //Get user data
        $userData = $this->getUserData();

        if ($userData) { // UPDATE USER VERIFICATION CODE
            $result = $this->updateUserCode($userData['id']);
            if ($result !== 'no') return $result;
        } else { // CREATE USER WITH ADD VERIFIVATION CODE
            $result = $this->createUser();
            if ($result !== 'no') return $result;
        }

        return [
            'data' => [
                'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring.',
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
        $checkResult = $this->checkUserActions(6);
        if ($checkResult !== 'ok') return $checkResult;

        // Get user data
        $userData = $this->getUserData();

        // Check user data
        if ($userData) {
            // Get user data
            $userId = $userData['id'];
            $code = $userData['code'];

            // Get user data from actions
            $userActionData = $this->getUserActionData();
            $action = $userActionData['urinish'] + 1;
            $actionUserId = $userActionData['id'];
            $actionTime = $userActionData['last'];

            // Check access time limit
            if (time() - $actionTime > $this->accessTimeLimit) {
                return [
                    'data' => [
                        'message' => 'Tasdiqlash vaqti tugadi!'
                    ],
                    'status_code' => 400
                ];
            }

            //Update user action
            $result = $this->updateUserAction($action, $actionUserId);
            if ($result !== 'ok') return $result;

            if ($code === $this->code) {
                $this->updateUserCode($userId);

                // Generate token
                $token = $this->generateToken();

                // Update user token
                $result = $this->updateUserToken($userId, $token);
                if ($result !== 'ok') return $result;

                // Update user last actve using token
                $result = $this->setUserLastActive($token);
                if ($result !== 'ok') return $result;

                //Update user action
                $result = $this->updateUserAction(0, $actionUserId);
                if ($result !== 'ok') return $result;

                return [
                    'data' => [
                        'message' => 'Muvaffaqiyatli! Tizimga kirishingiz mumkin.',
                        'token' => $token
                    ],
                    'status_code' => 200
                ];
            }
            return [
                'data' => [
                    'message' => 'Xatolik! Kod noto\'g\'ri.',
                ],
                'status_code' => '400'
            ];
        }
        return [
            'data' => [
                'error' => 'Noto\'g\'ri surov!',
            ],
            'status_code' => '404'
        ];
    }

    // Get UserData
    protected function getUserData($token = 'null')
    {
        // Change table
        $this->table = 'clients';
        // Get user data from clients
        $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon OR token=:token';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':telefon', $this->telefon);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // setUserLastAvtive
    private function setUserLastActive($token)
    {
        $time = time();
        $token = md5($token);
        $this->table = 'clients';
        $query = 'UPDATE ' . $this->table . ' SET last=:last WHERE token=:token';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':last', $time);
        $stmt->bindParam(':token', $token);
        if (!$stmt->execute()) {
            return [
                'data' => [
                    'message' => 'Ichki xatolik! Qaytadan urinib ko\'ring'
                ],
                'status_code' => 500
            ];
        }
        return 'ok';
    }

    // Check user actions
    protected function checkUserActions($count = null)
    {
        if (!$count || $count < !$this->maxLimitAction) return null;
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


        if ($lastAction > $count) {
            $time = time() - $lastActionTime;
            // Check user wait time
            if ($lastAction > $count && $lastAction <= ($count + 3)) {
                $this->waitTimeLimit = 120; // 2 minute
            } else if ($lastAction > ($count + 3) && $lastAction <= $this->maxLimitAction) {
                $this->waitTimeLimit = 1800; // 30 minute
            } else if ($lastAction > $this->maxLimitAction) {
                $this->waitTimeLimit = $this->waitMaxTimeLimit; // 20 hour
            } else if ($time > $this->waitMaxTimeLimit) { // end wait max time limit
                $result = $this->updateUserAction(0, $userId);
                if ($result !== 'ok') return $result;
            }

            $liveWaitTime = $this->waitTimeLimit - $time;
            if ($liveWaitTime > 0 && $time < $this->accessTimeLimit) {
                $formatTime = 0;
                if ($liveWaitTime <= 3600) {
                    $m = floor($liveWaitTime / 60);
                    $s = floor($liveWaitTime % 60);
                    $s = $s < 10 ? '0' . $s : $s;
                    $formatTime = $m . ':' . $s . ' soniyadan so\'ng qayta urinib ko\'ring';
                } else if ($liveWaitTime > 3600 && $liveWaitTime <= 86400) {
                    $h = floor($liveWaitTime / 3600);
                    $m = floor(($liveWaitTime % 3600) / 60);
                    $m = $m < 10 ? '0' . $m : $m;
                    $formatTime = $h . ':' . $m . ' soatdan so\'ng qayta urinib ko\'ring';
                }

                return [
                    'data' => [
                        'message' => 'Urinishlar soni ko\'p! ' . $formatTime,
                        'waitTime' => $liveWaitTime,
                    ],
                    'status_code' => 400,
                ];
            }
        }
        return 'ok';
    }

    // Send message to user phone
    protected function sendMessage($telefon, $code)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->messageUrl,
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
    protected function updateUserAction($actions, $userId)
    {
        $this->table = 'actions';
        // Create query
        $query = 'UPDATE ' . $this->table . ' SET urinish=:actions, last=:last WHERE id=:id';

        // Prepare statment
        $stmt = $this->conn->prepare($query);
        $time = time();
        // Bind data
        $stmt->bindParam(':actions', $actions);
        $stmt->bindParam(':last', $time);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        // Execute query
        if ($stmt->execute()) return 'ok';
        return [
            'data' => [
                'erorr' . 'Erorr: ' . $stmt->error,
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
            // Change table
            $this->table = 'clients';


            // Create query
            $query = 'INSERT INTO ' . $this->table . ' SET telefon=:telefon, code=:code, fio="", korxona="", region="", manzil="", shaxs_turi="", qarz=0, seriya="", balans=0, sana=0, lastfoiz=0, token="", parol="", last=0 ';

            // Prepare statment
            $stmt = $this->conn->prepare($query);

            // Bind data
            $stmt->bindParam(':telefon', $this->telefon);
            $stmt->bindParam(':code', $code);

            // Execute query with check
            if ($stmt->execute()) {
                $this->table = 'actions';
                $time = time();
                $action = 1;
                $query = 'INSERT INTO  ' . $this->table . ' SET last=:last, urinish=:action, telefon=:telefon';
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':last', $time);
                $stmt->bindParam(':action', $action);
                $stmt->bindParam(':telefon', $this->telefon);

                if ($stmt->execute()) {
                    $resTel = $this->telefon;
                    $resTel = '********' . ($resTel[strlen($resTel) - 2] . $resTel[strlen($resTel) - 1]);
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
    protected function updateUserCode($userId)
    {
        $code = $this->generateCode();

        // Send message
//    $result = $this->sendMessage($this->telefon, $code);
        $result = 'ok';
        if ($result === 'ok') {
            // Change table
            $this->table = 'clients';

            // Create query
            $query = 'UPDATE ' . $this->table . ' SET code=:code WHERE id=:userId';

            // Prepare statment
            $stmt = $this->conn->prepare($query);

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
                $resTel = '********' . ($resTel[strlen($resTel) - 2] . $resTel[strlen($resTel) - 1]);

                $userData['userData'] = [
                    'message' => 'Biz ' . $resTel . ' raqamingizga SMS orqali faollashtirish kodini yubordik!',
                    'accessTime' => $this->accessTimeLimit,
                    'code' => $code
                ];
                // return success message
                return [
                    'data' => $userData,
                    'status_code' => 200
                ];
            }
        }
        return 'no';
    }

    // Update user token
    private function updateUserToken($userId, $token)
    {

        // Update user token
        $token = md5($token);
        $this->table = 'clients';
        $query = 'UPDATE ' . $this->table . ' SET token=:token WHERE id=:userId';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':userId', $userId);
        if (!$stmt->execute()) {
            return [
                'data' => [
                    'error' => 'Erorr: ' . $stmt->error,
                ],
                'status_code' => 500
            ];
        }
        return 'ok';
    }

    // Get user action data
    protected function getUserActionData($telefon = null)
    {
        if (!$telefon)
            $telefon = $this->telefon;
        // change table
        $this->table = 'actions';

        $query = 'SELECT * FROM ' . $this->table . ' WHERE telefon=:telefon';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':telefon', $telefon);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Generate message code
    protected function generateCode()
    {
        return rand(10000, 99999);
    }

    // Generate token
    private function generateToken()
    {
        $token = rand(10000, 99999);
        $token .= time();
        return md5($token);
    }
}
