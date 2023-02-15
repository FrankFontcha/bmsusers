<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Controller\AppController;
use Cake\I18n\I18n;
use Cake\I18n\Time;
use Cake\Mailer\Email;
use Exception;
use Firebase\JWT\JWT;

/**
 * Users Controller
 *
 */

//Code by Frank Donald Fontcha.

class UsersController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('PO');

        $this->loadModel("Users");
    }

    public function beforeFilter(\Cake\Event\EventInterface $event)
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['register', 'login']);
    }

    // public function signapp() 
    // {
    //     $this->request->allowMethod(["OPTIONS", "POST"]);
    //     return $this->response->withType('application/json')->withStringBody(json_encode(true));
    // }

    // register new user
    public function register()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        $status = false;
        $message = "";
        $data = "";

        try {
            //code...
            // form data
            $formData = $this->request->getData();

            // email address check rules
            $empData = $this->Users->find()->where([
                "email" => $this->request->getData("email")
            ]);

            if ($empData->count() > 0) {
                // already exists
                $status = false;
                $message = "Email address already exists";
            } else {
                // email address check rules
                $empData = $this->Users->find()->where([
                    "username" => $this->request->getData("username")
                ]);

                if ($empData->count() > 0) {
                    // already exists
                    $status = false;
                    $message = "UserName already exists";
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
                    }
                }
            }

            $result = [
                "status" => $status,
                "message" => $message,
                "data" => $data
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
                    'fields' => ['username' => 'email', 'password' => 'password']
                ]
            ]
        ]);

        try {
            //code...
            $user = $this->Auth->identify();
            // regardless of POST or GET, redirect if user is logged in
            if ($user && $user['status'] == 1) {
                $result = [
                    "status" => true,
                    "message" => "get user",
                    "data" => $user,
                    'token' => $this->generateUserToken($user['id'])
                ];
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

        try {
            //code...
            // form data
            $formData = $this->request->getData();

            // email address check rules
            $empData = $this->Users->find()->where([
                "email" => $this->request->getData("email")
            ]);

            if ($empData->count() > 0) {
                // already exists
                $status = false;
                $message = "Email address already exists";
            } else {
                // email address check rules
                $empData = $this->Users->find()->where([
                    "username" => $this->request->getData("username")
                ]);

                if ($empData->count() > 0) {
                    // already exists
                    $status = false;
                    $message = "UserName already exists";
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
                    }
                }
            }

            $result = [
                "status" => $status,
                "message" => $message,
                "data" => $data,
                "err" => $empObject->getErrors(),
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

    public function getAll()
    {
        $this->request->allowMethod(["OPTIONS", "POST"]);

        // form data
        $formData = $this->request->getData();

        //
        $empData = $this->Users->find();

        $result = [
            "status" => true,
            "message" => "get data",
            "data" => $empData
        ];

        return $this->response->withType('application/json')->withStringBody(json_encode($result));
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
    
            $entity = $this->Users->get($this->request->getData("id"));
            
            if ($entity) {
                $formData = $this->request->getData();
                $empObject = $this->Users->patchEntity($entity, $formData);
    
                if ($rs = $this->Users->update($empObject)) {
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
        } catch(\Throwable $th) {
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
            
            if ($entity) {
                $this->request = $this->request->withData('status', 2);
                $formData = $this->request->getData();
                $entity->username = "dl__".$entity->username;
                $entity->email = "dl__".$entity->email;
                $empObject = $this->Users->patchEntity($entity, $formData);
    
                if ($rs = $this->Users->update($empObject)) {
                    // success response
                    $result = [
                        "status" => true,
                        "message" => "User has been deleted",
                        "data" => $rs,
                    ];
                } else {
                    // error response
                    $result = [
                        "status" => false,
                        "message" => "Failed to delete user",
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
        } catch(\Throwable $th) {
            $result = [
                "status" => false,
                "message" => "Error or user not found",
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
