<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 25/04/14
 * Time: 10:48 AM
 */

require_once dirname( __FILE__ ) . '/interface-html-helper.php';

class CTCI_HtmlHelper implements CTCI_HtmlHelperInterface {

	protected $runModuleKeyFunc = null;

	public function __construct( $runModuleKeyCallback ) {
		$this->runModuleKeyFunc = $runModuleKeyCallback;
	}

	public function showAJAXRunButton( $label, $key, $enabled = true ) {
		$classes = '';
		$attr = '';
		if ( ! $enabled ) {
			$attr .= 'disabled';
		} else {
			$classes .= 'ctci-enabled ';
		}
		$classes .= 'button button-primary button-large';
		echo '<form name="' . $key . '" action="#" method="post" id="' . $key . '">
			<input type="hidden" name="action" value="' . $key . '">
        <input type="submit" name="' . $key . '_submit" id="' . $key . '_submit" class="' . $classes . '" value="' . $label . '" ' . $attr . '>
        </form>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $("#ctci-run-page-loading").hide();
            var frm = $("#' . $key . '");
            frm.submit(function (ev) {
                $("#ctci-message-log").html("");
                $("input.ctci-enabled").prop("disabled", true);
                $("#ctci-run-page-loading").show();
                $.ajax({
                    type: frm.attr("method"),
                    url: ajaxurl,
                    data: frm.serialize(),
                    success: function (data) {
                        $("input.ctci-enabled").prop("disabled", false);
                        $("#ctci-run-page-loading").hide();
                        $("#ctci-message-log").html(data);
                    }
                });

                ev.preventDefault();
            });
        });
        </script>
    ';
	}

	public function showAJAXRunButtonFor( CTCI_DataProviderInterface $provider, CTCI_OperationInterface $operation, $enabled = true ) {
		$this->showAJAXRunButton(
			'Run ' . $provider->getHumanReadableName() . ' ' . $operation->getHumanReadableName(),
			call_user_func( $this->runModuleKeyFunc, $provider->getTag(), $operation->getTag() ),
			$enabled
		);
	}

	public function showActionButton( $actionValue, $inputName, $inputId, $buttonTitle, $enabled = true ) {
		$classes = '';
		$attr = '';
		if ( ! $enabled ) {
			$attr .= 'disabled';
		} else {
			$classes .= 'ctci-enabled ';
		}
		$classes .= 'button button-primary button-large';
		printf(
			'<form name="%1$s" action="#" method="post" id="%1$s">
			<input type="hidden" name="ctci_action" value="%1$s">
            <input type="submit" name="%2$s" id="%3$s" class="%5$s" value="%4$s" %6$s>',
			$actionValue, $inputName, $inputId, $buttonTitle, $classes, $attr
		);
		echo '</form>';
	}
}