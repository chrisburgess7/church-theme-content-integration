<?php
/**
 * Plugin Name: Church Theme Content Integration Tester
 * Description: A plugin for testing various techniques for use in CTCI
 * Version: 1.0
 * Author: Chris Burgess
 * License: GPL2
 */

add_action( 'admin_menu', 'ctci_tester_menu' );

function ctci_tester_menu() {
    add_options_page( 'CTCI Tester Options', 'CTCI Tester', 'manage_options', 'ctci-tester', 'ctci_tester_plugin_options' );
}

function ctci_tester_plugin_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    echo '<div class="wrap">';
    echo '<form name="test" action="#" method="post" id="test">';
    echo '
			<input type="hidden" name="action" value="runctcitest">
        <input type="submit" name="test" id="test" class="button button-primary button-large" value="test">
        </form>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            var frm = $("#test");
            frm.submit(function (ev) {
                $.ajax({
                    type: frm.attr("method"),
                    url: ajaxurl,
                    data: frm.serialize(),
                    success: function (data) {
                        $("#message").html(data);
                    }
                });

                ev.preventDefault();
            });
        });
        </script>
        <p id="message"></p>
    </div>
    ';

}

add_action('wp_ajax_runctcitest', 'ctci_tester_runtest');

function ctci_tester_runtest() {
	$post = array(
    'ID'             => 14,
	  'post_content'   => 'Here is the bio description, ya ya ya.',
	  'post_name'      => '',
	  'post_title'     => 'Chris Burgess',
	  'post_status'    => 'publish',
	  'post_type'      => 'ctc_person',
	  'post_author'    => null,
	  'post_excerpt'   => 'An exceprt',
	  //'post_category'  => [ array(<category id>, ...) ] // Default empty.
	  //'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
	  'tax_input'      => array( 'ctc_person_group' => array(2, 3) ) // For custom taxonomies. Default empty.
	);

	$id = wp_insert_post($post);
	
	update_post_meta(14, '_ctc_person_position', 'IT Administrator');
	update_post_meta(14, '_ctc_person_phone', '12345678');
	update_post_meta(14, '_ctc_person_email', 'test@gmail.com');
	update_post_meta(14, '_ctc_person_urls', 'www.facebook.com');

	echo '<br />' . (string)$id;
}