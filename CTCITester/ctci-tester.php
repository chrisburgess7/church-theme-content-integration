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

add_action('wp_ajax_runctcitest', 'ctci_tester_runtest4');

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

function ctci_tester_runtest2() {
    $terms = get_term(2, 'ctc_person_group');
    var_dump($terms);
    
    wp_update_term(2, 'ctc_person_group', array(
        'name' => 'My Group',
        'description' => 'This is now my group'
    ));
}

function ctci_tester_runtest3() {
    /** @var $wpdb wpdb */
        global $wpdb;
        $attachTable = $wpdb->prefix . 'ctci_ctcgroup_connect';

        $updateResult = $wpdb->update(
            $attachTable,
            array(
                'data_provider' => 'f1',
                'provider_group_id' => '2b4278'
            ),
            array(
                'term_id' => 3
            ),
            array('%s', '%s'),
            array('%d')
        );

        // an error occurred during update, so we don't know if attach record exists or not, abort
        if ($updateResult === false) {
           echo 'false';
           return;
        } elseif ($updateResult > 0) {
            // successful update
           echo 'true';
           return;
        }
        
        // if no update error and no rows affected, then we need a new entry
        $result = $wpdb->insert($attachTable, array(
               'data_provider' => 'f1',
                'term_id' => 3,
                'provider_group_id' => '2b4278'
            ), array('%s', '%d', '%s')
        );
        // replace with exceptions
        if ($result === false) {
            echo 'false';
            return;
        } else {
            echo 'true';
            return;
        }
    
    //var_dump($result);
}

function ctci_tester_runtest4() {
    getAttachedCTCGroup();
}

function getAttachedCTCGroup()
{
    /** @var $wpdb wpdb */
    global $wpdb;
    $attachTable = $wpdb->prefix . 'ctci_ctcgroup_connect';

    $ctcGroupConnectRow = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT term_id FROM $attachTable WHERE data_provider = %s AND provider_group_id = %s",
            'f1',
            '1b4278'
        ),
        ARRAY_A
    );

    // no attached group
    if ($ctcGroupConnectRow === null) {
        return null;
    }

    $ctcGroupTermRecord = get_term($ctcGroupConnectRow['term_id'], 'ctc_person_group', ARRAY_A);

    if ($ctcGroupTermRecord === null || is_wp_error($ctcGroupTermRecord)) {
        var_dump($ctcGroupTermRecord);
    }

    //$ctcGroup = new CTCI_CTCGroup($ctcGroupTermRecord['id'], $ctcGroupTermRecord['name'], $ctcGroupTermRecord['description']);
    var_dump($ctcGroupTermRecord);

    //return $ctcGroup;
}