<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Exception;
use Firebase\JWT\JWT;
use App\Controller\Api\UserBranchsController;
use App\Controller\Api\Common\UsersCommonController;

/**
 * Users Controller
 *
 */

//Code by Frank Donald Fontcha.

class UsersController extends BaseApiController
{
    protected $UserBranchsController;
    protected $UsersCommonController;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadModel("Users");
        $this->loadModel("UserBranchs");
        $this->UserBranchsController = new UserBranchsController();
        $this->UsersCommonController = new UsersCommonController();
    }

    // register new user
    public function register()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        $status = false;
        $message = "";
        $data = "";
        $error = "";

        try {
            //code...
            // form data
            $formData = $this->request->getData();

            // email address check rules
            $empData = $this->Users->find()->where([
                "email" => $this->request->getData("email")
            ]);

            if (!$this->PO->passwordValidator($this->request->getData("password"))) {
                $result = [
                    "status" => $status,
                    "error" => "Password should have at least 1 lowercase letter, 1 uppercase letter, 1 digit, 1 special character, and at least 8 characters long"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }

            if ($empData->count() > 0) {
                // already exists
                $status = false;
                $message = "Email address already exists";
                $error = (isset($empObject)) ? $empObject->getErrors() : "";
            } else {
                // email address check rules
                $empData = $this->Users->find()->where([
                    "username" => $this->request->getData("username")
                ]);

                if ($empData->count() > 0) {
                    // already exists
                    $status = false;
                    $message = "UserName already exists";
                    $error = (isset($empObject)) ? $empObject->getErrors() : "";
                } else {
                    // insert new user
                    $empObject = $this->Users->newEmptyEntity();

                    $empObject = $this->Users->patchEntity($empObject, $formData);

                    if ($rs = $this->Users->save($empObject)) {
                        // success response
                        $status = true;
                        $message = "Users has been created";
                        $data = $rs;
                    } else {
                        // error response
                        $status = false;
                        $message = "Failed to create user";
                        $error = (isset($empObject)) ? $empObject->getErrors() : "";
                    }
                }
            }

            $result = [
                "status" => $status,
                "message" => $message,
                "data" => $data,
                "error" => $error
            ];

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        } catch (\Throwable $th) {
            //throw $th;
            $result = [
                "status" => false,
                "message" => "An error occured",
                "error" => $th
            ];

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    public function login()
    {
        $this->loadComponent('Auth', [
            'authenticate' => [
                'Form' => [
                    'fields' => ['username' => 'email', 'password' => 'password'],
                ]
            ]
        ]);

        try {
            //code...
            $user = $this->Auth->identify();
            // regardless of POST or GET, redirect if user is logged in
            if ($user) {
                if ($user['status'] == 1) {
                    $result = [
                        "status" => true,
                        "message" => "get user",
                        "data" => $user,
                        'token' => $this->generateUserToken($user['id'])
                    ];
                } else if ($user['status'] != 2) {
                    $result = [
                        "status" => true,
                        "message" => "Your account is disable please contact admin",
                        "data" => []
                    ];
                }
            } else {
                $result = [
                    "status" => false,
                    "message" => "Couldn't connect email or password invalid"
                ];
            }

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        } catch (\Throwable $th) {
            //throw $th;
            $result = [
                "status" => false,
                "message" => $th
            ];

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    // create new user
    public function createUser()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        $status = false;
        $message = "";
        $data = "";
        $user_id = 0;

        try {
            //code...
            // email address check rules
            $empData = $this->Users->find()->where([
                "email" => $this->request->getData("email"),
                "status !=" => 2
            ]);

            if (!$this->PO->passwordValidator($this->request->getData("password"))) {
                $result = [
                    "status" => $status,
                    "error" => "Password should have at least 1 lowercase letter, 1 uppercase letter, 1 digit, 1 special character, and at least 8 characters long"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }

            if ($empData->count() > 0) {
                // already exists
                $status = false;
                $message = "Email address already exists";
            } else {
                // email address check rules
                $empData = $this->Users->find()->where([
                    "username" => $this->request->getData("username"),
                    "status !=" => 2
                ]);

                if ($empData->count() > 0) {
                    // already exists
                    $status = false;
                    $message = "UserName already exists";
                } else {
                    // insert new user
                    $empObject = $this->Users->newEmptyEntity();
                    // form data
                    $formData = $this->request->getData();

                    $empObject = $this->Users->patchEntity($empObject, $formData);

                    if ($this->request->getData("branchs") == null || sizeof($this->request->getData("branchs")) == 0) {
                        $result = [
                            "status" => false,
                            "message" => "Branchs data not found or invalid branchs datas",
                        ];

                        return $this->response->withType('application/json')->withStringBody(json_encode($result));
                    }

                    if ($rs = $this->Users->save($empObject)) {
                        // success response
                        $status = true;
                        $message = "Users has been created";
                        $data = $rs;
                        $user_id = $rs->id;

                        $data = $this->UsersCommonController->addUserToBranchs($this->request->getData("branchs"), $rs->id);  // add user to branchs
                    } else {
                        // error response
                        $status = false;
                        $message = "Failed to create user";
                    }
                }
            }

            $result = [
                "status" => $status,
                "message" => $message,
                "data" => $data,
                "err" => (isset($empObject)) ? $empObject->getErrors() : "",
            ];

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        } catch (\Throwable $th) {
            //throw $th;
            $result = [
                "status" => false,
                "message" => "An error occured",
                "error" => $th
            ];

            if ($user_id != 0) {
                $user_data = $this->Users->get($user_id);
                $this->Users->delete($user_data);
            }

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    public function getAll()
    {
        try {
            $this->request->allowMethod(["OPTIONS", "POST"]);

            $array_users_id = [];

            $getuserbranchs = $this->UsersCommonController->getUserByBranchs($this->request);
            if ($getuserbranchs != null) {
                $array_users_id = array_merge($array_users_id, $getuserbranchs);
            }

            $getowner = $this->UsersCommonController->getBusinessOwnerByBranchId($this->request);
            if ($getowner && isset($getowner->busines->user_id)) {
                array_push($array_users_id, $getowner->busines->user_id);
            }

            $conditions = [
                'Users.id !=' => $this->request->getData("user_id"),
                'Users.id IN' => $array_users_id,
                'Users.status IN' => (strval($this->request->getData('status')) != null && strval($this->request->getData('status')) != "2") ? [$this->request->getData('status')] : [0, 1]
            ];

            //
            $empData = $this->Users->find()->where($conditions)->contain(['Branchs', 'BranchOwner']);

            $result = [
                "status" => true,
                "message" => "get data",
                "data" => $empData
            ];

            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        } catch (\Throwable $th) {
            $result = [
                "status" => false,
                "message" => "Error or users not found",
                "err" => $th
            ];
            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    public function update()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        try {
            if ($this->request->getData("id") == null) {
                $result = [
                    "status" => false,
                    "message" => "user's id is required"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }

            if ($this->request->getData("status") != null && !in_array($this->request->getData("status"), [0, 1])) {
                $result = [
                    "status" => false,
                    "message" => "Invalid user status should be 0 : inactive or 1 : active"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }

            $entity = $this->Users->get($this->request->getData("id"));

            if ($entity && (isset($entity->status) && $entity->status != 2)) {
                $formData = $this->request->getData();
                $empObject = $this->Users->patchEntity($entity, $formData);

                if ($rs = $this->Users->save($empObject)) {
                    // success response
                    $result = [
                        "status" => true,
                        "message" => "User has been updated",
                        "data" => $rs,
                    ];
                } else {
                    // error response
                    $result = [
                        "status" => false,
                        "message" => "Failed to update user",
                        "data" => $rs,
                        "err" => $empObject->getErrors(),
                    ];
                }

                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            } else {
                $result = [
                    "status" => false,
                    "message" => "user not found"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }
        } catch (\Throwable $th) {
            $result = [
                "status" => false,
                "message" => "Error or user not found",
                "err" => $th
            ];
            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    public function delete()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        try {
            if ($this->request->getData("id") == null) {
                $result = [
                    "status" => false,
                    "message" => "user's id is required"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }

            $entity = $this->Users->get($this->request->getData("id"));

            if ($entity && (isset($entity->status) && $entity->status != 2)) {

                $entity->username = "dl__" . $entity->username;
                $entity->email = "dl__" . $entity->email;
                $entity->status = 2;

                if ($rs = $this->Users->save($entity)) {
                    // success response
                    $result = [
                        "status" => true,
                        "message" => "User has been deleted"
                    ];
                } else {
                    // error response
                    $result = [
                        "status" => false,
                        "message" => "Failed to delete user",
                        "data" => $rs,
                        "err" => (isset($empObject)) ? $empObject->getErrors() : "",
                    ];
                }

                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            } else {
                $result = [
                    "status" => false,
                    "message" => "user not found"
                ];
                return $this->response->withType('application/json')->withStringBody(json_encode($result));
            }
        } catch (\Throwable $th) {
            $result = [
                "status" => false,
                "message" => "Error or user not found.",
                "err" => $th
            ];
            return $this->response->withType('application/json')->withStringBody(json_encode($result));
        }
    }

    public function generateUserToken($userid)
    {
        $payload = [
            'iss' => 'bmsusers',
            'sub' => $userid,
            'exp' => time() + 3600,
        ];
        $privateKey = file_get_contents(CONFIG . '/jwt.key');

        return JWT::encode($payload, $privateKey, 'RS256');
    }
}
