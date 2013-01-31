<?php
//requiresd variables
assert($QSG_ID);//question gruop's ID
assert($question_group);//the main question group
assert($question_group instanceof EE_Question_Group);
assert(isset($all_questions) && (empty($all_questions) || is_array($all_questions)));//list of unused questions
foreach($all_questions as $unused_question){
	assert($unused_question);
	assert($unused_question instanceof EE_Question);
}
?>

<div class="edit-group padding">
	<table class="form-table">
		<tbody>
			<tr>
				<td width="60%" valign="top">
							
					<table class="form-table">
						<tbody>
							<tr>
								<th>
									<label for="QSG_name">
										<?php _e('Group Name','event_espresso');?>
									</label>
								</th>
								<td>
									<input name="QSG_name" value="<?php echo $question_group->name()?>" type="text" class="regular-text"><br/>
									<span class="description">
										<?php _e('A name or heading for this group of questions that can be used to organize your Registration Form. For example: Address Information.','event_espresso')?>
									</span>
								</td>
							</tr>
							<tr>
								<th>
									<label for="QSG_identifier">
										<?php _e('Group Identifier','event_espresso');?>
									</label>
								</th>
								<td>
									<input disabled name="QSG_identifier" value="<?php echo $question_group->identifier()?>" type="text" class="regular-text"><br/>
									<span class="description">
										<?php _e('The "Group Identifier" is a unique name for this group that can be used to distinguish it from all other groups in the system. A Group Identifier therefore can not be the same as any other. It will NOT be displayed to site visitors. If left blank, one will be automagically generated for you, ie: address-info-12345.','event_espresso')?>
									</span>
								</td>
							</tr>
							<tr>
								<th>
									<label for="QSG_desc">
										<?php _e('Description','event_espresso');?>
									</label>
								</th>
								<td>
									<textarea name="QSG_desc"  class="regular-text" rows="1" cols="50">
										<?php echo $question_group->desc()?>
									</textarea>
								</td>
							</tr>
							<tr>
								<th>
									<label for="QSG_order">
										<?php _e('Group Order','event_espresso');?>
									</label>
								</th>
								<td>
									<input name="QSG_order" value="<?php echo $question_group->order()?>" type="text" class="small-text">
								</td>
							</tr>
							<tr>
								<th>
									<label>
										<?php _e('Show Group Name','event_espresso');?>
									</label>
								</th>
								<td>
									<label for="QSG_show_group_name">
										<input type="checkbox" name="QSG_show_group_name" value="<?php echo $question_group->show_group_name()?>"> &nbsp;
										<?php _e('Show Group Name on Registration Page?','event_espresso');?>
									</label>
								</td>
							</tr>
							<tr>
								<th>
									<label>
										<?php _e(' Show Group Description','event_espresso');?>
									</label>
								</th>
								<td>
									<label for="QSG_show_group_order">
										<input type="checkbox" name="QSG_show_group_order" value="<?php echo $question_group->show_group_desc()?>"> &nbsp;
										<?php _e(' Show Group Description on Registration Page?','event_espresso');?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
				
				<td width="40%" valign="top">
					<div class="padding">
						<h6><?php _e('Questions that appear in this group.','event_espresso');?></h6>
						<ul>
							<?php 
							foreach( $all_questions as $question_ID=>$question ){
								/*@var $question EE_Question*/
								$checked = array_key_exists( $question_ID, $question_group->questions() ) ? ' checked="checked"' : '';
							?>
							<li>
								<label for="question-<?php echo $question_ID?>">
									<input type="checkbox" name="questions[<?php echo $question_ID;?>]" id="question-<?php echo $question_ID;?>" value="<?php echo $question_ID;?>"<?php echo $checked;?>/>
									<?php echo $question->display_text()?>				
								</label>
							</li>
							<?php }?>
						</ul>
					</div>
				</td>
				
			</tr>
		</tbody>
	</table>
</div>