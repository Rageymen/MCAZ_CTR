<?php
namespace App\Controller\Base;

use App\Controller\AppController;
use Cake\Validation\Validation;
use Cake\Event\Event;
use Cake\Log\Log;
use Cake\Auth\DefaultPasswordHasher;

/**
 * Users Controller
 *
 * @property \App\Model\Table\UsersTable $Users
 *
 * @method \App\Model\Entity\User[] paginate($object = null, array $settings = [])
 */
class UsersBaseController extends AppController
{
    public $paginate = [
            // 'limit' => 2,
            'Applications' => ['scope' => 'application'],
            'Amendments' => ['scope' => 'amendment'],
        ];

    public function initialize() {
       parent::initialize();
       $this->loadComponent('Paginator');
       $this->loadModel('Users');      
    }

    
    public function dashboard() {
        $this->loadModel('Applications');
        $this->paginate = [
            'limit' => 15,
        ];

        $app_query = $this->Applications->find('all')->where(['submitted' => 2, 'report_type' => 'Initial', 'Applications.status' => 'Submitted']);
        //Evaluators and External evaluators only to view if assigned
        if ($this->Auth->user('group_id') == 3 or $this->Auth->user('group_id') == '6') {
            $app_query->matching('AssignEvaluators', function ($q) {
                return $q->where(['AssignEvaluators.assigned_to' => $this->Auth->user('id')]);
            });
        }
        // Secretary General only able to view once it has been approved
        if ($this->Auth->user('group_id') == 7) {
            $app_query->matching('ApplicationStages', function ($q) {
                return $q->where(['ApplicationStages.stage_id' => 9]);
            });
        }

        $applications = $this->paginate($app_query, ['scope' => 'application', 'order' => ['Applications.status' => 'asc', 'Applications.id' => 'desc'],
                                    'fields' => ['Applications.id', 'Applications.created', 'Applications.protocol_no', 'Applications.submitted']]);
        $amt_query = $this->Users->Amendments->find('all', array(
            //'fields' => array('id','user_id', 'created', 'protocol_no', 'public_title', 'submitted'),
            'order' => array('Amendments.created' => 'desc'),
            'contain' => ['ParentApplications'],
            'conditions' => ['Amendments.submitted' => 2, 'Amendments.report_type' => 'Amendment', 'Amendments.status' => 'Submitted'],
        ));
        if ($this->Auth->user('group_id') == 3 or $this->Auth->user('group_id') == '6') {
            $amt_query->matching('AssignEvaluators', function ($q) {
                return $q->where(['AssignEvaluators.assigned_to' => $this->Auth->user('id')]);
            });
        }
        // Secretary General only able to view once it has been approved
        if ($this->Auth->user('group_id') == 7) {
            $amt_query->matching('ApplicationStages', function ($q) {
                return $q->where(['ApplicationStages.stage_id' => 9]);
            });
        }
        
        $amendments = $this->paginate($amt_query, 
            ['scope' => 'amendment']);

        $this->set(compact('applications', 'amendments'));
        $this->render('/Base/Users/dashboard');
    }
}
