<?php
/**
* Rights installer class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.9.3
*/
class RightsInstaller extends CApplicationComponent
{
	/**
	* @var CDbAuthManager
	*/
	private $_authManager;
	/**
	* @var CDbConnection
	*/
	public $db;
	/**
	* @var bool Whether Rights is installed or not?
	*/
	public $isInstalled;

	/**
	* Initialization.
	*/
	public function init()
	{
		$this->_authManager = Yii::app()->authManager;
		$this->db = $this->_authManager->db;
		$this->isInstalled = self::isInstalled();

		parent::init();
	}

	/**
	* Installs the Rights module.
	* @param string $superUserRole Name of the super user role
	* @param array $superUsers List of super users (id=>name)
	* @return bool
	*/
	public function install($defaultRoles, $superUserRole, $superUsers)
	{
		// Make sure that the module is not already installed
		if( $this->isInstalled===false )
		{
			// Get table names
			$itemTable = $this->_authManager->itemTable;
			$itemChildTable = $this->_authManager->itemChildTable;
			$assignmentTable = $this->_authManager->assignmentTable;

			// Start transaction
			$txn = $this->db->beginTransaction();

			try
			{
				// Drop tables if they already exist
				$sql = "drop table if exists {$itemTable}";
				$command = $this->db->createCommand($sql);
				$command->execute();
				$sql = "drop table if exists {$itemChildTable}";
				$command = $this->db->createCommand($sql);
				$command->execute();
				$sql = "drop table if exists {$assignmentTable}";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// AuthItem
				$sql = "create table {$itemTable} ( ";
				$sql.= "	name varchar(64) not null, ";
				$sql.= "	type integer not null, ";
				$sql.= "	description text, ";
				$sql.= "	bizrule text, ";
				$sql.= "	data text, ";
				$sql.= "	primary key (name) ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// AuthChild
				$sql = "create table {$itemChildTable} ( ";
				$sql.= "	parent varchar(64) not null, ";
				$sql.= "	child varchar(64) not null, ";
				$sql.= "	primary key (parent, child), ";
				$sql.= "	foreign key (parent) references {$itemTable} (name) on delete cascade on update cascade, ";
				$sql.= "	foreign key (child) references {$itemTable} (name) on delete cascade on update cascade ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// AuthAssignment
				$sql = "create table {$assignmentTable} ( ";
				$sql.= "	itemname varchar(64) not null, ";
				$sql.= "	userid varchar(64) not null, ";
				$sql.= "	bizrule text, ";
				$sql.= "	data text, ";
				$sql.= "	primary key (itemname, userid), ";
				$sql.= "	foreign key (itemname) references {$itemTable} (name) on delete cascade on update cascade ";
				$sql.= ") type=InnoDB";
				$command = $this->db->createCommand($sql);
				$command->execute();

				// Insert the necessary roles
				$roles = array_merge($defaultRoles, array($superUserRole));
				foreach( $roles as $roleName )
				{
					$sql = "insert into {$itemTable} (name, type) values (:name, :type)";
					$command = $this->db->createCommand($sql);
					$command->bindValue(':name', $roleName);
					$command->bindValue(':type', CAuthItem::TYPE_ROLE);
					$command->execute();
				}

				// Set super users
				foreach( $superUsers as $id=>$username )
				{
					$sql = "insert into {$assignmentTable} (itemname, userid) values (:itemname, :userid)";
					$command = $this->db->createCommand($sql);
					$command->bindValue(':itemname', $superUserRole);
					$command->bindValue(':userid', $id);
					$command->execute();
				}

				// All commands executed successfully, commit
				$txn->commit();
				return true;
			}
			catch( CDbException $e )
			{
				// Something went wrong, rollback
				$txn->rollback();
				return false;
			}
		}
	}

	/**
	* Checks if the Rights is already installed.
	* @return bool
	*/
	public function isInstalled()
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

			return true;
		}
		catch( CDbException $e )
		{
			return false;
		}
	}
}
