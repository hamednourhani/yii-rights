<?php
/**
* Rights installer component class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.9.3
*/
class RightsInstaller extends CApplicationComponent
{
	/**
	* @property array the roles assigned to users implicitly.
	*/
	public $defaultRoles;
	/**
	* @property string the name of the superuser role.
	*/
	public $superuserName;
	/**
	* @property string the name of the authenticated role.
	*/
	public $authenticatedName;
	/**
	* @property string the name of the guest role.
	*/
	public $guestName;

	private $_authManager;
	private $_installed;

	/**
	* @property CDbConnection
	*/
	public $db;

	/**
	* Initializes the installer.
	*/
	public function init()
	{
		parent::init();

		// Get the authorization manager and make sure
		// the application uses a valid authorization manager.
		$authManager = Yii::app()->getAuthManager();
		if( ($authManager instanceof RightsAuthManager)===false )
			throw new CException(Rights::t('install', 'You must configure your application to use the "RightsAuthManager".'));

		$this->_authManager = $authManager;
		$this->db = $this->_authManager->db;
	}

	/**
	* Runs the installer.
	* @param boolean whether to drop tables if they exists.
	* @return boolean whether the installer ran successfully.
	*/
	public function run($overwrite=false)
	{
		// Run the installer only if the module is not already installed
		// or if we wish to overwrite the existing tables.
		if( $this->isInstalled===false || $overwrite===true )
		{
			$itemTable = $this->_authManager->itemTable;
			$itemChildTable = $this->_authManager->itemChildTable;
			$assignmentTable = $this->_authManager->assignmentTable;
			$rightsTable = $this->_authManager->rightsTable;

			// Start transaction
			$txn = $this->db->beginTransaction();

			try
			{
				// Drop tables if necessary
				if( $overwrite===true )
					$this->dropTables();

				// Create the AuthItem-table.
				$sql = "CREATE TABLE {$itemTable} (
					name varchar(64) not null,
					type integer not null,
					description text,
					bizrule text,
					data text,
					primary key (name)
					) type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the AuthChild-table.
				$sql = "CREATE TABLE {$itemChildTable} (
					parent varchar(64) not null,
					child varchar(64) not null,
					primary key (parent, child),
					foreign key (parent) references {$itemTable} (name) on delete cascade on update cascade,
					foreign key (child) references {$itemTable} (name) on delete cascade on update cascade
					) type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the AuthAssignment-table.
				$sql = "CREATE TABLE {$assignmentTable} (
					itemname varchar(64) not null,
					userid varchar(64) not null,
					bizrule text,
					data text,
					primary key (itemname, userid),
					foreign key (itemname) references {$itemTable} (name) on delete cascade on update cascade
					) type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Create the Rights-table.
				$sql = "CREATE TABLE {$rightsTable} (
					itemname varchar(64) not null,
					type integer not null,
					weight integer,
					primary key (itemname),
					foreign key (itemname) references {$itemTable} (name) on delete cascade on update cascade
					) type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Insert the necessary roles.
				$roles = $this->getUniqueRoles();
				foreach( $roles as $roleName )
				{
					$sql = "INSERT INTO {$itemTable} (name, type, data)
						VALUES (:name, :type, :data)";
					$command = $this->db->createCommand($sql);
					$command->bindValue(':name', $roleName);
					$command->bindValue(':type', CAuthItem::TYPE_ROLE);
					$command->bindValue(':data', 'N;');
					$command->execute();
				}

				// Assign the logged in user the superusers role.
				$sql = "INSERT INTO {$assignmentTable} (itemname, userid, data)
					VALUES (:itemname, :userid, :data)";
				$command = $this->db->createCommand($sql);
				$command->bindValue(':itemname', $this->superuserName);
				$command->bindValue(':userid', Yii::app()->getUser()->id);
				$command->bindValue(':data', 'N;');
				$command->execute();

				// All commands executed successfully, commit.
				$txn->commit();
				return true;
			}
			catch( CDbException $e )
			{
				// Something went wrong, rollback.
				$txn->rollback();
				return false;
			}
		}
	}

	/**
	* Drops the tables in the correct order.
	*/
	private function dropTables()
	{
		$sql = "DROP TABLE IF EXISTS {$this->_authManager->rightsTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "DROP TABLE IF EXISTS {$this->_authManager->assignmentTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "DROP TABLE IF EXISTS {$this->_authManager->itemChildTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();

		$sql = "DROP TABLE IF EXISTS {$this->_authManager->itemTable}";
		$command = $this->db->createCommand($sql);
		$command->execute();
	}

	/**
	* Returns a list of unique roles names.
	* @return array the list of roles.
	*/
	private function getUniqueRoles()
	{
		$roles = array($this->superuserName, $this->authenticatedName, $this->guestName);
		$roles = array_merge($roles, $this->defaultRoles);
		return array_unique($roles);
	}

	/**
	* @return boolean whether Rights is installed.
	*/
	public function getInstalled()
	{
		if( $this->_installed===null )
		{
			try
			{
				$sql = "SELECT COUNT(*) FROM {$this->_authManager->itemTable}";
				$command = $this->db->createCommand($sql);
				$command->queryScalar();

				$sql = "SELECT COUNT(*) FROM {$this->_authManager->itemChildTable}";
				$command = $this->db->createCommand($sql);
				$command->queryScalar();

				$sql = "SELECT COUNT(*) FROM {$this->_authManager->assignmentTable}";
				$command = $this->db->createCommand($sql);
				$command->queryScalar();

				$sql = "SELECT COUNT(*) FROM {$this->_authManager->rightsTable}";
				$command = $this->db->createCommand($sql);
				$command->queryScalar();

				$this->_installed = true;
			}
			catch( CDbException $e )
			{
				$this->_installed = false;
			}
		}

		return $this->_installed;
	}
}
