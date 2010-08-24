<?php
/**
* Rights assignment controller class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.9.1
*/
class AssignmentController extends Controller
{
	/**
	* @var RightsModule
	*/
	private $_module;
	/**
	* @var RightsAuthorizer
	*/
	private $_authorizer;

	/**
	* Initializes the controller.
	*/
	public function init()
	{
		$this->_module = $this->getModule();
		$this->_authorizer = $this->_module->getAuthorizer();
		$this->layout = $this->_module->layout;
		$this->defaultAction = 'view';

		// Register the scripts
		$this->_module->registerScripts();
	}

	/**
	* @return array action filters
	*/
	public function filters()
	{
		return array('accessControl');
	}

	/**
	* Specifies the access control rules.
	* This method is used by the 'accessControl' filter.
	* @return array access control rules
	*/
	public function accessRules()
	{
		return array(
			array('allow', // Allow superusers to access Rights
				'actions'=>array(
					'view',
					'user',
					'revoke',
				),
				'users'=>$this->_authorizer->getSuperusers(),
			),
			array('deny', // Deny all users
				'users'=>array('*'),
			),
		);
	}

	public function actionView()
	{
		$userClass = $this->_module->userClass;

		$criteria = new CDbCriteria();
		$count = CActiveRecord::model($userClass)->count($criteria);

		// Create the pager
		$pages = new CPagination($count);
		$pages->pageSize = 20;
		$pages->applyLimit($criteria);

		// Get all users
		$users = CActiveRecord::model($userClass)->findAll($criteria);

		// Collect the ids
		$userIdList = array();
		foreach( $users as $u )
		{
			$u->attachBehavior('rights', new RightsUserBehavior);
			$userIdList[] = $u->getId();
		}

		// Get the assigned authorization items for all user
		$userAssignments = $this->_authorizer->getUserAssignments($userIdList);

		// Create a list of assignments with beautified names for each user
		// and place them in a list of assignment with the user id as key
		$assignments = array();
		foreach( $userAssignments as $userId=>$items )
			foreach( $items as $name=>$item )
				$assignments[ $userId ][] = Rights::beautifyName($name);

		// Render the view
		$this->render('view', array(
			'users'=>$users,
			'assignments'=>$assignments,
			'pages'=>$pages,
		));
	}

	/**
	* Displays the auth assignments for an user.
	*/
	public function actionUser()
	{
		$userClass = $this->_module->userClass;

		$model = CActiveRecord::model($userClass)->findByPk($_GET['id']);
		$model->attachBehavior('rights', new RightsUserBehavior);
		$assignedAuthItems = $this->_authorizer->getAuthItems(null, $model->getId());

		// Get the assigned items
		$assignedItems = array();
		foreach( $assignedAuthItems as $item )
			$assignedItems[] = $item->getName();

		// Get the assignment select options
		$selectOptions = $this->_authorizer->getAuthItemSelectOptions(null, null, null, true, $assignedItems);

		// Create a from to add a child for the authorization item
	    $form = new CForm('rights.views.assignment.assignmentForm', new AssignmentForm);
	    $form->elements['authItem']->items = $selectOptions; // Populate authorization items

		// Form is submitted and data is valid, redirect the user
	    if( $form->submitted()===true && $form->validate()===true )
		{
			// Update and redirect
			$this->_authorizer->authManager->assign($form->model->authItem, $model->getId());
			Yii::app()->user->setFlash($this->_module->flashSuccessKey,
				Yii::t('RightsModule.core', ':name assigned.', array(':name'=>Rights::beautifyName($form->model->authItem)))
			);
			$this->redirect(array('assignment/user', 'id'=>$model->getId()));
		}

		// Render the view
		$this->render('user', array(
			'model'=>$model,
			'form'=>$form,
			'assignedItems'=>$assignedItems,
		));
	}

	/**
	* Revokes an assignment from an user.
	*/
	public function actionRevoke()
	{
		// We only allow deletion via POST request
		if( Yii::app()->request->isPostRequest===true )
		{
			$this->_authorizer->authManager->revoke($_GET['name'], $_GET['id']);
			Yii::app()->user->setFlash($this->_module->flashSuccessKey,
				Yii::t('RightsModule.core', ':name revoked.', array(':name'=>Rights::beautifyName($_GET['name'])))
			);

			// if AJAX request, we should not redirect the browser
			if( isset($_POST['ajax'])===false )
				$this->redirect(array('assignment/user', 'id'=>$_GET['id']));
		}
		else
		{
			throw new CHttpException(400, Yii::t('RightsModule.core', 'Invalid request. Please do not repeat this request again.'));
		}
	}
}
