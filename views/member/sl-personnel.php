<?php if ($member->position_id == 5) : ?><!-- if squad leader -->

	<?php 

	$squad_id = Squad::mySquadId($member->id); 
	if (property_exists($squad_id, 'id')) { $squadMembers = arrayToObject(Squad::findSquadMembers($squad_id));}
	else { $squadMembers = false; }
	?>

	<?php if (count((array) $squadMembers)) : ?>

		<div class='panel panel-primary'>
			<div class='panel-heading'><strong><?php echo $member->forum_name ?>'s Squad</strong> <span class="pull-right"><?php echo count((array) $squadMembers); ?> members</span></div>
			<div class='list-group'>
				<?php if ($squadMembers): ?>
					<?php foreach($squadMembers as $player) : ?>
						<a href='member/<?php echo $player->member_id ?>' class='list-group-item'><input type='checkbox' data-id='<?php echo $player->member_id; ?>' class='pm-checkbox'><span class='member-item'><?php echo Rank::convert($player->rank_id)->abbr ?> <?php echo $player->forum_name ?></span><small class='pull-right text-<?php echo inactiveClass($player->last_activity); ?>'>Seen <?php echo formatTime(strtotime($player->last_activity)); ?></small></a>
					<?php endforeach; ?>	
				<?php endif; ?>			
			</div>
		</div>
	<?php endif; ?>

<?php endif; ?>