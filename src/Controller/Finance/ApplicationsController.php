<?php

namespace App\Controller\Finance;

use App\Controller\Base\ApplicationsBaseController;
use Cake\ORM\Entity;
use Cake\View\Helper\HtmlHelper;
use Cake\Utility\Hash;

/**
 * Applications Controller
 *
 * @property \App\Model\Table\ApplicationsTable $Applications
 *
 * @method \App\Model\Entity\Application[] paginate($object = null, array $settings = [])
 */
class ApplicationsController extends ApplicationsBaseController
{
  public function index($id = null)
  {
    parent::index($id);
    $this->render('/Finance/Applications/index');
  }

  public function view($id = null)
  {
    parent::view($id);
    $this->render('/Finance/Applications/view');
  }

  public function financeApproval($id = null)
  {
    $application = $this->Applications->get($this->request->getData('application_pr_id'), ['contain' => ['ApplicationStages', 'ParentApplications']]);

    // debug($application);
    if (isset($application->id) && $this->request->is(['patch', 'post', 'put'])) {
      $application = $this->Applications->patchEntity(
        $application,
        $this->request->getData(),
        ['associated' => ['FinanceApprovals.Attachments']]
      );

      $application->finance_approvals[0]->user_id = $this->Auth->user('id');
      if ($application->finance_approvals[0]->outcome == 'Fees Complete') {
        //new stage only once
        if (!in_array("2", Hash::extract($application->application_stages, '{n}.stage_id'))) {
          $stage1  = $this->Applications->ApplicationStages->newEntity();
          $stage1->stage_id = 2;
          $stage1->stage_date = date("Y-m-d H:i:s");
          $stage1->alt_date = $application->finance_approvals[0]->outcome_date;
          $application->application_stages = [$stage1];
          $application->status = 'Finance';
          $application->action_date= date("Y-m-d H:i:s");
          //Generate Refined Protocol Number
          $refid = $this->Applications->Refids->newEntity(
            [
              'foreign_key' => $application->id,
              'model' => 'Applications',
              'year' => date('Y')
            ]
          );
          $this->Applications->Refids->save($refid);
          $refid = $this->Applications->Refids->get($refid->id);
          $application->protocol_no = 'CT' . $refid->refid . '/' . $refid->year;
          $this->Applications->save($application);
        }
      }
      //Notification should be sent to manager and assigned_to evaluator if exists
      // debug($application);
      // return;
      if ($this->Applications->save($application)) {
        //Send email and message (if present!!!) 
        $this->loadModel('Queue.QueuedJobs');
        //notify managers and finance
        $managers = $this->Applications->Users->find('all')->where(['Users.group_id IN' => [2, 5]]);
        foreach ($managers as $manager) {
          $data = [
            'email_address' => $manager->email, 'user_id' => $manager->id, 'model' => 'Applications', 'foreign_key' => $application->id,
            'vars' =>  $application->toArray()
          ];
          $data['type'] = 'finance_submit_approval_email';
          $data['vars']['name'] = $manager->name;
          $data['vars']['public_comments'] = $application->finance_approvals[0]->public_comments;
          $data['vars']['internal_comments'] = $application->finance_approvals[0]->internal_comments;
          $this->QueuedJobs->createJob('GenericEmail', $data);
          $data['type'] = 'finance_submit_approval_notification';
          $this->QueuedJobs->createJob('GenericNotification', $data);
        }
        //

        //applicant visible notification and email sent 
        if (!empty($application->finance_approvals[0]->public_comments)) {
          $reporter = $this->Applications->Users->get($application->user_id);
          $data = [
            'email_address' => $application->email_address, 'user_id' => $application->user_id,
            'type' => 'applicant_finance_comments_email', 'model' => 'Applications', 'foreign_key' => $application->id,
            'vars' =>  $application->toArray()
          ];
          $data['vars']['name'] = $reporter['name'];
          $data['vars']['public_comments'] = $application->finance_approvals[0]->public_comments;
          //notify applicant
          $this->QueuedJobs->createJob('GenericEmail', $data);
          $data['type'] = 'applicant_finance_comments_notification';
          $this->QueuedJobs->createJob('GenericNotification', $data);
        }
        //end 

        $this->Flash->success('Finance Review successfully done for Application ' . $application->protocol_no);

        return $this->redirect($this->referer());
      } else {
        $this->Flash->error(__('Unable to submit report.'));
        //debug($application->errors());
        return $this->redirect($this->referer());
      }
    } else {
      $this->Flash->error(__('Unknown Application. Please correct.'));
      return $this->redirect($this->referer());
    }
  }

  public function financeAnnualApproval($id = null)
  {
    $application = $this->Applications->get($this->request->getData('application_pr_id'), ['contain' => ['ParentApplications']]);

    // debug($application);
    if (isset($application->id) && $this->request->is(['patch', 'post', 'put'])) {
      $application = $this->Applications->patchEntity($application, $this->request->getData());

      $application->finance_annual_approvals[0]->user_id = $this->Auth->user('id');
      //Notification should be sent to manager and assigned_to evaluator if exists
      // return;
      if ($this->Applications->save($application)) {
        //Send email and message (if present!!!) 
        $this->loadModel('Queue.QueuedJobs');
        //notify managers and finance
        $managers = $this->Applications->Users->find('all')->where(['Users.group_id IN' => [2, 5]]);
        foreach ($managers as $manager) {
          $data = [
            'email_address' => $manager->email, 'user_id' => $manager->id, 'model' => 'Applications', 'foreign_key' => $application->id,
            'vars' =>  $application->toArray()
          ];
          $data['type'] = 'finance_submit_approval_email';
          $data['vars']['name'] = $manager->name;
          $data['vars']['public_comments'] = $application->finance_annual_approvals[0]->public_comments;
          $data['vars']['internal_comments'] = $application->finance_annual_approvals[0]->internal_comments;
          $this->QueuedJobs->createJob('GenericEmail', $data);
          $data['type'] = 'finance_submit_approval_notification';
          $this->QueuedJobs->createJob('GenericNotification', $data);
        }
        //

        //applicant visible notification and email sent 
        if (!empty($application->finance_annual_approvals[0]->public_comments)) {
          $reporter = $this->Applications->Users->get($application->user_id);
          $data = [
            'email_address' => $application->email_address, 'user_id' => $application->user_id,
            'type' => 'applicant_finance_comments_email', 'model' => 'Applications', 'foreign_key' => $application->id,
            'vars' =>  $application->toArray()
          ];
          $data['vars']['name'] = $reporter['name'];
          $data['vars']['public_comments'] = $application->finance_annual_approvals[0]->public_comments;
          //notify applicant
          $this->QueuedJobs->createJob('GenericEmail', $data);
          $data['type'] = 'applicant_finance_comments_notification';
          $this->QueuedJobs->createJob('GenericNotification', $data);
        }
        //end 

        $this->Flash->success('Finance Review successfully done for Annual approval for ' . $application->protocol_no);

        return $this->redirect($this->referer());
      } else {
        $this->Flash->error(__('Unable to submit report.'));
        //debug($application->errors());
        return $this->redirect($this->referer());
      }
    } else {
      $this->Flash->error(__('Unknown Application. Please correct.'));
      return $this->redirect($this->referer());
    }
  }
}
