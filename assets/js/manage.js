$(function() {

    $(".draggable").draggable({
        connectToSortable: 'ul',
        revert: 'invalid',
        scroll: true,
        scrollSensitivity: 100
    });

    $(".view-profile").click(function() {
        var userId = $(this).closest('.list-group-item').attr('data-user-id');
        location.href = "member/" + userId;
    });

    $(".create-squad").click(function(e) {
        e.preventDefault();
        $(".viewPanel .viewer").load("create/squad", {
            division_id: $(this).attr('data-division-id'),
            platoon_id: $(this).attr('data-platoon-id')
        });
        $(".viewPanel").modal();
    });

    $(".modal").delegate("#create_squad_btn", "click", function(e) {
        e.preventDefault();
        var data = $("#create_squad").serialize();
        $.post("do/create-squad", data, function() {
            $(".viewPanel").modal('hide');
            setTimeout(function() {
                location.reload();
            }, 600);
        });
    });


    $(".modify-squad").click(function(e) {
        e.preventDefault();
        $(".viewPanel .viewer").load("modify/squad", {
            division_id: $(this).closest('div').find('ul').attr('data-division-id'),
            platoon_id: $(this).closest('div').find('ul').attr('data-platoon-id'),
            squad_id: $(this).closest('div').find('ul').attr('data-squad-id')
        });
        $(".viewPanel").modal();
    });


    $(".modal").delegate("#modify_squad_btn", "click", function(e) {
        e.preventDefault();
        var data = $("#modify_squad").serialize();
        $.post("do/modify-squad", data, function() {
            $(".viewPanel").modal('hide');
            setTimeout(function() {
                location.reload();
            }, 600);
        });
    });




    var itemMoved, targetplatoon, sourcePlatoon, action = null;

    $(".sortable").sortable({

        revert: true,
        connectWith: 'ul',
        placeholder: "ui-state-highlight",
        receive: function(event, ui) {

            itemMoved = $(ui.item).attr('data-player-id');
            targetList = $(this).attr('id');

            if (targetList == "flagged-inactives") {
                $(ui.item).find('.removed-by').show().html("Flagged by you");
                action = 1;
                context = " flagged for removal.";

                var flagCount = parseInt($(".flagCount").text()) + 1,
                    inactiveCount = parseInt($(".inactiveCount").text()) - 1;

                $(".flagCount").text(flagCount);
                $(".inactiveCount").text(inactiveCount);


            } else {

                $(ui.item).find('.removed-by').empty();
                context = " no longer flagged for removal."
                action = 0;

                var flagCount = parseInt($(".flagCount").text()) - 1,
                    inactiveCount = parseInt($(".inactiveCount").text()) + 1;

                $(".flagCount").text(flagCount);
                $(".inactiveCount").text(inactiveCount);

            }

            var member_id = $("#member_id").val();

            $.ajax({

                type: 'POST',
                url: 'do/update-flag',
                data: {
                    action: action,
                    id: itemMoved,
                    member_id: member_id
                },

                dataType: 'json',
                success: function(response) {
                    if (response.success === false) {
                        message = response.message;
                        $(".alert-box").stop().html("<div class='alert alert-danger'><i class='fa fa-times'></i> " + message + "</div>").effect('highlight').delay(1000).fadeOut();
                    } else {
                        message = "Player " + itemMoved + context;
                        $(".alert-box").stop().html("<div class='alert alert-success'><i class='fa fa-check'></i> " + message + "</div>").effect('highlight').delay(1000).fadeOut();
                    }
                },

                // fail: function()
            });

        }
    });

    // manage division

    var itemMoved, targetplatoon, sourcePlatoon;
    $(".sortable-division").sortable({
        connectWith: 'ul',
        placeholder: "ui-state-highlight",
        receive: function(event, ui) {
            itemMoved = $(ui.item).attr('data-member-id');
            targetPlatoon = $(this).attr('id');
            alert("Player " + itemMoved + " now belongs to " + targetPlatoon);

        }
    });

    // manage platoon

    var itemMoved, targetplatoon, sourcePlatoon;

    $(".mod-plt .sortable").sortable({
        connectWith: 'ul',
        placeholder: "ui-state-highlight",
        receive: function(event, ui) {
            itemMoved = $(ui.item).attr('data-member-id');
            targetSquad = $(this).attr('data-squad-id');
            senderLength = $(ui.sender).find('li').length;
            receiverLength = $(this).find('li').length;

            if (undefined == targetSquad) {

                alert("You cannot move players to this list");
                $(".mod-plt .sortable").sortable('cancel');

            } else {

                // is genpop empty?
                if ($('.genpop').find('li').length < 1) {
                    $('.genpop').fadeOut();
                }

                // update squad counts
                $(ui.sender).parent().find('.badge').text(senderLength);
                $(this).parent().find('.badge').text(receiverLength);

                $.ajax({

                    type: 'POST',
                    url: 'do/update-member-squad',
                    data: {
                        member_id: itemMoved,
                        squad_id: targetSquad
                    },

                    dataType: 'json',
                    success: function(response) {
                        if (response.success === false) {
                            message = response.message;

                            $(".alert-box").stop().html("<div class='alert alert-danger'><i class='fa fa-times'></i> " + message + "</div>").effect('highlight').delay(1000).fadeOut();
                        } else {
                            //message = "Player " + itemMoved + context;
                            message = "Player #" + itemMoved + " reassigned";

                            $(".alert-box").stop().html("<div class='alert alert-success'><i class='fa fa-check'></i> " + message + "</div>").effect('highlight').delay(1000).fadeOut();
                        }
                    },

                    // fail: function()
                });

            }

        }
    });




    /**
     * LOA management
     */

    // pending view loa

    var view_pending_loa = "<div class='viewer fadeIn animate'><div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'><i class='fa fa-times-circle'></i></span></button><h4>Review Leave of Absence</h4></div><div class='modal-body'><strong>Reason for request</strong>: <textarea class='form-control' style='resize:vertical; min-height: 200px;' id='comment' class='comment' /></div><div class='modal-footer'><div class='btn-group'><button type='button' class='btn btn-success approve-loa-btn'>Approve</button> <button type='button' class='btn btn-danger deny-loa-btn'>Deny</button><button type='button' data-dismiss='modal' class='btn'>Close</button></div></div></div></div>";

    $(".view-pending-loa").click(function() {
        var id = $(this).closest('tr').attr('data-member-id'),
            loa_id = $(this).closest('tr').attr('data-loa-id'),
            comment = $(this).closest('tr').attr('data-comment');

        $(".viewPanel .viewer").html(view_pending_loa);
        $(".modal #comment").html(comment);

        $(".modal").attr('data-member-id', id).modal();
        $(".modal").attr('data-loa-id', loa_id).modal();
    })



    // deny LOA

    $(".modal").delegate(".deny-loa-btn", "click", function(e) {

        e.preventDefault();

        var url = "do/update-loa",
            member_id = $('.modal').attr('data-member-id'),
            loa_id = $('.modal').attr('data-loa-id');

        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: {
                remove: true,
                member_id: member_id,
                loa_id: loa_id
            },

            success: function(data) {
                if (data.success) {

                    $('.modal').modal('hide');

                    $('*[data-loa-id="' + loa_id + '"]').effect('highlight').hide("fade", {
                        direction: "out"
                    }, "slow");
                    $(".loa-alerts").attr('class', 'alert alert-success loa-alerts').html("<i class='fa fa-check fa-lg'></i> " + data.message).show().delay(3000).fadeOut();

                } else {
                    $(".loa-alerts").attr('class', 'alert alert-danger loa-alerts').html("<i class='fa fa-exclamation-triangle fa-lg'></i> " + data.message).show().delay(3000).fadeOut();
                    $('.modal').modal('hide');
                }
            }
        });

    })


    // approve LOA
    $(".modal").delegate(".approve-loa-btn", "click", function(e) {

        e.preventDefault();

        var url = "do/update-loa",
            member_id = $('.modal').attr('data-member-id'),
            loa_id = $('.modal').attr('data-loa-id');

        $.ajax({
            type: "POST",
            url: url,
            dataType: 'json',
            data: {
                approve: true,
                member_id: member_id,
                loa_id: loa_id
            },

            success: function(data) {
                if (data.success) {

                    $('.modal').modal('hide');

                    $('*[data-loa-id="' + loa_id + '"]').effect('highlight').hide("fade", {
                        direction: "out"
                    }, "slow");
                    $(".loa-alerts").attr('class', 'alert alert-success loa-alerts').html("<i class='fa fa-check fa-lg'></i> " + data.message).show().delay(3000).fadeOut();

                } else {
                    $(".loa-alerts").attr('class', 'alert alert-danger loa-alerts').html("<i class='fa fa-exclamation-triangle fa-lg'></i> " + data.message).show().delay(3000).fadeOut();
                    $('.modal').modal('hide');
                }
            }
        });

    })



    // active view loa

    $('#loas').delegate(".view-active-loa", "click", function(e) {
        var id = $(this).closest('tr').attr('data-loa-id'),
            member_id = $(this).closest('tr').attr('data-member-id'),
            comment = $(this).closest('tr').attr('data-comment'),
            approval = $(this).closest('tr').attr('data-approval');

        $(".viewPanel .viewer").html(view_active_loa);
        $(".modal #comment").html(comment);
        $(".modal #approval").val(approval);

        $(".modal").attr('data-loa-id', id).modal();
        $(".modal").attr('data-member-id', member_id).modal();

    })


    var view_active_loa = "<div class='viewer fadeIn animate'><div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'><i class='fa fa-times-circle'></i></span></button><h4>Review Leave of Absence</h4></div><div class='modal-body'><p><strong>Approved by</strong>: <input class='form-control' id='approval' /></p><p><strong>Reason for request</strong>: <textarea class='form-control' style='resize:vertical; min-height: 200px;' id='comment' class='comment' /></p></div><div class='modal-footer'> <div class='btn-group'> <button type='button' class='btn btn-primary pm-btn'>PM Player</button><button type='button' class='btn btn-danger revoke-loa-btn'>Revoke</button>  <button type='button' data-dismiss='modal' class='btn'>Close</button></div></div></div></div>";

    // revoke LOA
    var revoke_confirm = "<div class='viewer fadeIn animate'><div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'><i class='fa fa-times-circle'></i></span></button><h4>Confirm revoke leave of absence</h4></div><div class='modal-body'><p>Once a player's LOA is revoked, their status must be updated on the forums. Additionally, if this is a revocation, the member should be flagged for removal.</p></div><div class='modal-footer'> <div class='btn-group'><button type='button' data-dismiss='modal' class='btn btn-primary' id='delete'>Revoke LOA</button> <button type='button' data-dismiss='modal' class='btn'>Cancel</button></div></div></div></div>";

    $(".modal").delegate(".revoke-loa-btn", "click", function(e) {

        e.preventDefault();

        $(".viewPanel .viewer").html(revoke_confirm);

        var url = "do/update-loa",
            loa_id = $('.modal').attr('data-loa-id'),
            member_id = $('.modal').attr('data-member-id');

        $('.modal').modal({
            backdrop: 'static',
            keyboard: false
        })
            .one('click', '#delete', function(e) {
                $.ajax({
                    type: "POST",
                    url: url,
                    dataType: 'json',
                    data: {
                        remove: true,
                        loa_id: loa_id,
                        member_id: member_id
                    },

                    success: function(data) {
                        if (data.success) {
                            $('*[data-loa-id="' + loa_id + '"]').effect('highlight').hide("fade", {
                                direction: "out"
                            }, "slow");
                            $(".loa-alerts").attr('class', 'alert alert-success loa-alerts').html("<i class='fa fa-check fa-lg'></i> " + data.message).show().delay(3000).fadeOut();

                            $('.modal').modal('hide');

                        } else {
                            $(".loa-alerts").attr('class', 'alert alert-danger loa-alerts').html("<i class='fa fa-exclamation-triangle fa-lg'></i> " + data.message).show().delay(3000).fadeOut();
                            $('.modal').modal('hide');
                        }
                    }
                });
            });

    })

    var add_loa = "<div class='viewer fadeIn animate'><div class='modal-header'><button type='button' class='close' data-dismiss='modal' aria-label='Close'><span aria-hidden='true'><i class='fa fa-times-circle'></i></span></button><h4>Request Leave of Absence</h4></div><div class='modal-body'><label for='comment' class='control-label'>Reason for request</label>: <textarea class='form-control' style='resize:vertical; min-height: 100px;' id='comment' name='comment' class='comment' placeholder='Provide an explanation for your leave of absence request. Please include a reference link to the post requesting an LOA.' required /></div><div class='modal-footer'><div class='btn-group'> <button type='button' data-dismiss='modal' class='btn'>Cancel</button> <button type='button' id='submit' class='btn btn-success'>Submit</button> </div></div></div></div>";


    // LOA ADD
    $("#loa-update").submit(function(e) {
        e.preventDefault();

        var url = "do/update-loa";

        $(".viewPanel .viewer").html(add_loa);

        $('.modal').modal({
            backdrop: 'static',
            keyboard: false
        })
            .one('click', '#submit', function(e) {

                var comment = $(".modal #comment").val();
                $.ajax({
                    type: "POST",
                    url: url,
                    dataType: 'json',
                    data: $("#loa-update").serialize() + "&comment=" + comment,
                    success: function(data) {
                        if (data.success) {
                            var $newRow = $("<tr data-id='" + data.id + "'><td>" + data.name + "</td><td>" + data.reason + "</td><td>" + data.date + "</td><td class='text-center'><h4><span class='label bg-warning'><i class='fa fa-check fa-lg' title='Pending'></i> Pending</span></h4></td><td class='text-center loa-actions' style='opacity: .2;'><button class='btn btn-default btn-block' title='Review LOA' disabled>Review LOA</button></td></tr>");

                            $("#loas tbody tr:last").after($newRow);
                            $newRow.effect("highlight", {}, 3000);
                            $('#loa-update')[0].reset();

                            $('.modal').modal('hide');

                        } else {
                            $('.modal').modal('hide');
                            $(".loa-alerts").attr('class', 'alert alert-danger loa-alerts').html("<i class='fa fa-exclamation-triangle fa-lg'></i> " + data.message).show().delay(3000).fadeOut();

                        }
                    }
                });


                return false;

            });

    });


    $("#datepicker").datepicker({
        changeMonth: true,
        changeYear: true
    });


    // contact
    $(".modal").delegate(".pm-btn", "click", function(e) {
        var pm_url = 'http://www.clanaod.net/forums/private.php?do=newpm&u=' + $('.modal').attr('data-id');
        windowOpener(pm_url, "Mass PM", "width=900,height=600,scrollbars=yes");
    });


    var stickyElement = '.sticky-box',
        bottomElement = '#footer .navbar';

    if ($(stickyElement).length) {
        $(stickyElement).each(function() {

            var fromTop = $(this).offset().top,
                fromBottom = $(document).height() - ($(this).offset().top + $(this).outerHeight()),
                stopOn = $(document).height() - ($(bottomElement).offset().top) + ($(this).outerHeight() - $(this).height());

            if ((fromBottom - stopOn) > 200) {
                $(this).css('width', $(this).width()).css('top', 0).css('position', '');
                $(this).affix({
                    offset: {
                        top: fromTop,
                        bottom: stopOn
                    }
                }).on('affix.bs.affix', function() {
                    $(this).css('top', 0).css('position', '');
                });
            }

            $(window).trigger('scroll');
        });
    }

});