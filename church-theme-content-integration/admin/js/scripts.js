/**
 * Created by Chris on 30/04/14.
 */

jQuery(document).ready(function($){
    $("#ctci-run-page-loading-box").hide();
    var frm = $(".ctci-sync-form");
    frm.submit(function (ev) {
        $("#ctci-message-log").html("");
        $("input.ctci-enabled").prop("disabled", true);
        $("#ctci-run-page-loading-box").addClass('ctci-run-page-loading').show();
        $.ajax({
            type: frm.attr("method"),
            url: ajaxurl,
            data: frm.serialize(),
            success: function (data) {
                $("input.ctci-enabled").prop("disabled", false);
                $("#ctci-run-page-loading-box").removeClass('ctci-run-page-loading').hide();
                $("#ctci-message-log").html(data);
            }
        });

        ev.preventDefault();
    });
});