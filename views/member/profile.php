<div class='container'>

    <ul class='breadcrumb'>
        <li><a href='./'>Home</a></li>
        <li><a href='divisions/<?php echo $divisionInfo->short_name; ?>'><?php echo $divisionInfo->full_name; ?></a>
        </li>
        <?php echo (property_exists($platoonInfo, 'link')) ? $platoonInfo->link : null; ?>
        <li class='active'><?php echo $memberInfo->forum_name; ?></li>
    </ul>

    <div class='page-header vertical-align'>
        <div class='col-xs-1 hidden-sm hidden-xs'>
            <?php echo Member::avatar($memberInfo->member_id, 'large'); ?></div>

        <div class='col-xs-5'>
            <h2>
                <strong><?php echo Rank::convert($memberInfo->rank_id)->abbr . " " . $memberInfo->forum_name; ?></strong>
                <br /><a class='btn btn-default btn-xs popup-link'
                         href='<?php echo PRIVMSG . $memberInfo->member_id ?>&url=<?php echo CLANAOD . $memberInfo->member_id ?>'
                         target='_blank'><i class='fa fa-comment'></i> Send PM</a><a
                        class='btn btn-default btn-xs popup-link'
                        href='<?php echo EMAIL . $memberInfo->member_id ?>&url=<?php echo CLANAOD . $memberInfo->member_id ?>'
                        target='_blank'><i class='fa fa-envelope'></i> Send Email</a>
            </h2>
        </div>

        <div class='col-xs-6'>

            <div class='btn-group pull-right' data-player-id='<?php echo $memberInfo->member_id ?>'
                 data-user-id='<?php echo $member->member_id ?>'>

                <?php if (User::canEdit($memberInfo->member_id, $user, $member)) : ?>
                    <button type='button' class='btn btn-info edit-member'><i class="fa fa-pencil fa-lg"></i> Edit
                    </button>
                <?php endif; ?>

                <?php if ($member->rank_id >= 9 && $memberInfo->status_id != 4) : ?>
                    <a href="<?php echo REMOVE ?>" title="Remove player from AOD"
                       class='removeMember btn btn-danger'><i class='fa fa-trash fa-lg'></i> Remove<span
                                class="hidden-sm hidden-xs"> from AOD</span></a>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <!-- page data -->

    <?php echo $alerts ?>

    <div class="margin-top-20">
        <!-- Nav tabs -->
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#activity" aria-controls="home" role="tab"
                                                      data-toggle="tab">Server Activity</a></li>
            <?php if ($user->role >= 1 && $memberInfo->position_id == 5) : ?>
                <li role="presentation"><a href="#squadmembers" aria-controls="squadmembers" role="tab"
                                           data-toggle="tab"><?php echo Locality::run('Squad', $memberInfo->game_id); ?>
                        members</a></li>
            <?php endif; ?>
            <li role="presentation"><a href="#recruits" aria-controls="recruits" role="tab" data-toggle="tab">Recruiting
                    records</a></li>
            <li role="presentation"><a href="#history" aria-controls="history" role="tab" data-toggle="tab">Member
                    history</a></li>

        </ul>

    </div>

    <div class='row margin-top-20'>

        <div class='col-md-8'>

            <div role="tabpanel">

                <!-- Tab panes -->
                <div class="tab-content">
                    <div role="tabpanel" class="tab-pane active" id="activity">
                        <?php echo $activity ?>
                    </div>
                    <?php if ($user->role >= 1 && $memberInfo->position_id == 5) : ?>
                        <div role="tabpanel" class="tab-pane" id="squadmembers">
                            <?php echo $sl_personnel ?>
                        </div>
                    <?php endif; ?>
                    <div role="tabpanel" class="tab-pane" id="recruits">
                        <?php echo $recruits ?>
                    </div>
                    <div role="tabpanel" class="tab-pane" id="history">
                        <?php echo $history ?>
                    </div>

                </div>

            </div>

        </div><!-- end right side -->

        <div class='col-md-4'>
            <?php echo $member_data ?>
        </div>

    </div><!-- end row -->
</div><!-- end container -->
