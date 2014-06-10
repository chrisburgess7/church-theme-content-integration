/**
 * Created by Chris Burgess on 30/04/14.
 */

jQuery(document).ready(function($){
    //$("#ctci-run-page-loading-box").hide();

    // all sync run buttons
    var frm = $(".ctci-sync-form");
    frm.submit(function (ev) {
        var $this = $(this);
        var $runSection = $this.closest(".ctci-run-section");
        var $indicator = $runSection.find(".ctci-run-indicator");
        var $statusUpdate = $runSection.find(".ctci-run-update");

        var statusCheckId;

        // disable all buttons
        $("input.ctci-enabled").prop("disabled", true);
        // add spinner
        $indicator.addClass("ctci-spinner");
        // empty log section
        $("#ctci-message-log").html("");
        // remove any status messages
        CTCIRunStatus.clear($(".ctci-run-update"));

        $.ajax({
            type: frm.attr("method"),
            url: ajaxurl,
            data: frm.serialize(),
            success: function (data) {
                // enable buttons
                $("input.ctci-enabled").prop("disabled", false);
                // remove spinner
                $indicator.removeClass("ctci-spinner");
                // stop the status check running on the interval
                clearInterval(statusCheckId);
                // set the completion message
                var parsedData = JSON.parse(data);
                if (parsedData == false) {
                    CTCIRunStatus.update($statusUpdate, ctci_translations.ajax_response_not_json_upon_completion);
                } else {
                    CTCIRunStatus.setFromObject($statusUpdate, parsedData);
                }
            }
        });

        statusCheckId = setInterval( function(){
            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: 'action=ctci_check_status',
                success: function(data) {
                    var parsedData = JSON.parse(data);
                    if (parsedData !== false) {
                        CTCIRunStatus.setFromObject($statusUpdate, parsedData, 'update');
                    }
                }
            })
        }, 3000 );

        ev.preventDefault();
    });

    // view log button
    var $log = $("#ctci_getlog");
    var $submit = $log.find('input[type=submit]');
    var $spinnerDiv = $('#ctci-log-viewer').find('.ctci-run-indicator');
    $log.submit(function (ev) {
        $submit.prop("disabled", true);
        $spinnerDiv.addClass("ctci-spinner");
        $("#ctci-message-log").html("");
        $.ajax({
            type: $log.attr("method"),
            url: ajaxurl,
            data: $log.serialize(),
            success: function (data) {
                $submit.prop("disabled", false);
                $spinnerDiv.removeClass("ctci-spinner");
                $("#ctci-message-log").html(data);
            }
        });

        ev.preventDefault();
    })
});