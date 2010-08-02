<?php $this->breadcrumbs = array(
	'Rights'=>array('/rights'),
	Yii::t('RightsModule.tr', 'Assignments')=>array('/rights/assignment/view'),
	$model->$username,
); ?>

<div id="userAssignments" class="span-12 first">

	<h2><?php echo Yii::t('RightsModule.tr', 'Assignments for :username', array(':username'=>$model->$username)); ?></h2>

	<?php if( count($assignedItems)>0 ): ?>

		<table class="rightsMiniTable userAssignmentTable" border="0" cellpadding="0" cellspacing="0">

			<tbody>

				<?php $i=0; foreach( $assignedItems as $itemName ): ?>

					<tr class="<?php echo ($i++ % 2)===0 ? 'odd' : 'even'; ?>">

						<td><?php echo CHtml::link(Rights::beautifyName($itemName), array('authItem/update', 'name'=>$itemName)); ?></td>

						<td class="revokeColumn">
							<?php echo CHtml::linkButton(Yii::t('RightsModule.tr', 'Revoke'), array(
								'submit'=>array('assignment/revoke', 'id'=>$model->id, 'name'=>$itemName),
								'confirm'=>Yii::t('RightsModule.tr', 'Are you sure to revoke this assignment?'),
								'class'=>'revokeLink',
							)); ?>
						</td>

					</tr>

				<?php endforeach; ?>

			</tbody>

		</table>

	<?php else: ?>

		<p class="rightsInfo"><?php echo Yii::t('RightsModule.tr', 'This user has not been assigned any auth items.'); ?></p>

	<?php endif; ?>

</div>

<div id="addUserAssignment" class="span-11 last">

	<h3><?php echo Yii::t('RightsModule.tr', 'Add Assignment'); ?></h3>

	<div class="rightsForm form">

		<?php echo $form->render(); ?>

	</div>

</div>