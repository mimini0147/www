<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of UserController
 *
 * @author jian
 */
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class UsersController extends AppController {

    var $uses = array('NoteDefaultConfig', 'User', 'UserConfig');
    var $components = array('Utility');

    public function beforeFilter() {
        parent::beforeFilter();
        // Permet aux utilisateurs de s'enregistrer et de se déconnecter
        $this->Auth->allow('register', 'login', 'userBySecurityKey');
    }

    public function userBySecurityKey() {
        $this->autoRender = false;
        $user = null;
        $security_key = "";
        if (isset($this->request->data['security_key'])) {

            $security_key = $this->request->data['security_key'];
            $user = $this->User->find('threaded', array(
                'conditions' => array('security_key' => $security_key),
                'recursive' => 0));
        }
        if ($user && count($user)>0) {
            return json_encode($user[0]);
        }
        $error = array();
        $error['error'] = "Cannot find user by " + $security_key;
        return json_encode($error);
    }

    public function login() {
        if ($this->request->is('post')) {
            if ($this->Auth->login()) {
                $user = $this->Auth->user();
                $user['last_connect_date'] = $this->Utility->DateTimeNow();
                $user['last_connect_ip'] = $_SERVER['REMOTE_ADDR'];
                $user['security_key'] = UtilityComponent::GUID();

                if ($this->User->save($user)) {
                    $this->Auth->login(); //reprendre tous les informations
                    return $this->redirect($this->Auth->redirectUrl());
                }
            } else {
                $this->Session->setFlash('Compte ou mot de passe incorrect:' . $this->Auth->login());
            }
        }
        return $this->redirect(array('controller' => 'pages', 'action' => 'index'));
    }

    public function logout() {
        return $this->redirect($this->Auth->logout());
    }

    public function register() {
        $error = "-";
        if ($this->request->is('post')) {
            $this->User->create();

            if (count($this->User->findByEmail($this->request->data['User']['email'])) == 0) {
                $this->request->data['User']['create_date'] = $this->Utility->DateTimeNow();
                //$dataSource = $this->getDateSource();
                $ok = false;
                //  $dataSource->begin();
                if ($this->User->save($this->request->data)) {
                    $user_id = $this->User->getInsertID();


                    $default_config = $this->NoteDefaultConfig->genereSimpleNoteConfig($user_id);
                    if ($this->UserConfig->save($this->UserConfig->genereSimpleUserConfig($user_id)) &&
                            $this->NoteDefaultConfig->saveMany($default_config)) {
                        $this->Session->setFlash('User registred');
                    }
                } else {
                    $error = "Impossible de s'inscrire:" . print_r($this->User->validationErrors, true);
                }
                /* if($ok){
                  $dataSource->commit();
                  }else{
                  $dataSource->rollback();
                  } */
            } else {
                $error = 'User ' . $this->request->data['User']['email'] . ' already exists';
            }
        }
        $this->Session->setFlash($error);
        return $this->redirect(array('controller' => 'pages', 'action' => 'index'));
    }

    public function profil() {
        
    }

}
