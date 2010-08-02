<?php
/**
* Authorization item form class file.
*
* @author Christoffer Niska <cniska@live.com>
* @copyright Copyright &copy; 2010 Christoffer Niska
* @since 0.5
*/
class AuthItemForm extends CFormModel
{
	public $name;
	public $description;
	public $type;
	public $bizRule;
	public $data;

	/**
	* Declares the validation rules.
	*/
	public function rules()
	{
		return array(
			array('name, description', 'required'),
			array('type', 'required', 'on'=>'create'),
		   	array('type, bizRule, data', 'safe'),
		);
	}

	/**
	* Declares attribute labels.
	*/
	public function attributeLabels()
	{
		return array(
			'name'			=> Yii::t('RightsModule.tr', 'Name'),
			'description'	=> Yii::t('RightsModule.tr', 'Description'),
			'type'			=> Yii::t('RightsModule.tr', 'Type'),
			'bizRule'		=> Yii::t('RightsModule.tr', 'Business rule'),
			'data'			=> Yii::t('RightsModule.tr', 'Data'),
		);
	}
}
