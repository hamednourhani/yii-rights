<?php
$this->breadcrumbs = array(
	'Rights'=>array('/rights/main'),
	Yii::t('RightsModule.tr', 'Roles'),
);
?>

<div class="rights">

	<?php $this->renderPartial('/_menu'); ?>

	<?php $this->renderPartial('/_flash'); ?>

	<div id="rightsRoles">

		<h2><?php echo Yii::t('RightsModule.tr', 'Roles'); ?></h2>

		<?php if( count($roles)>0 ): ?>

			<table class="rightsTable roleTable sortableTable" border="0" cellpadding="0" cellspacing="0">

				<thead>

					<tr>

						<th class="nameColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Name'); ?></th>

						<th class="descriptionColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Description'); ?></th>

						<?php if( $isBizRuleEnabled===true ): ?>

							<th class="bizRuleColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Business rule'); ?></th>

							<?php if( $isBizRuleDataEnabled===true ): ?>

								<th class="dataColumnHeading"><?php echo Yii::t('RightsModule.tr', 'Data'); ?></th>

							<?php endif; ?>

						<?php endif; ?>

						<th class="deleteColumnHeading">&nbsp;</th>

					</tr>

				</thead>

				<tbody>

					<?php $i=0; foreach( $roles as $name=>$item ): ?>

						<tr id="<?php echo $name; ?>" class="<?php echo ($i++ % 2)===0 ? 'odd' : 'even'; ?>">

							<td>

								<?php echo CHtml::link(Rights::beautifyName($name), array('authItem/update', 'name'=>$name, 'redirect'=>urlencode('main/roles'))); ?>

								<?php if( $name===Rights::getConfig('superUserRole') ): ?>

									<span class="superUser">( <span class="superUserText"><?php echo Yii::t('RightsModule.tr', 'Super user'); ?></span> )</span>

								<?php endif; ?>

								<?php if( $childCounts[ $name ]>0 ): ?>

									<span class="childCount">[ <span class="childCountNumber"><?php echo $childCounts[ $name ]; ?></span> ]</span>

								<?php endif; ?>

							</td>

							<td><?php echo CHtml::encode($item->description); ?></td>

							<?php if( $isBizRuleEnabled===true ): ?>

								<td class="bizRuleColumn"><?php echo CHtml::encode($item->bizRule); ?></td>

								<?php if( $isBizRuleDataEnabled===true ): ?>

									<td class="bizRuleDataColumn"><?php echo $item->data!==null ? CHtml::encode( @serialize($item->data) ) : ''; ?></td>

								<?php endif; ?>

							<?php endif; ?>

							<td class="deleteColumn">

								<?php if( $name!==Rights::getConfig('superUserRole') ): ?>

									<?php echo CHtml::linkButton(Yii::t('RightsModule.tr', 'Delete'), array(
										'submit'=>array('authItem/delete', 'name'=>$name, 'redirect'=>urlencode('main/roles')),
										'confirm'=>Yii::t('RightsModule.tr', 'Are you sure to delete this role?'),
										'class'=>'deleteLink',
									)); ?>

								<?php endif; ?>

							</td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

			<p class="rightsInfo floatLeft"><?php echo Yii::t('RightsModule.tr', 'Values within square brackets tell how many children each item has.'); ?></p>

			<p class="rightsInfo floatRight"><?php echo Yii::t('RightsModule.tr', 'Roles can be reorganized by dragging and dropping.'); ?></p>

		<?php else: ?>

			<p><?php echo Yii::t('RightsModule.tr', 'No roles found.'); ?></p>

		<?php endif; ?>

	</div>

</div>
