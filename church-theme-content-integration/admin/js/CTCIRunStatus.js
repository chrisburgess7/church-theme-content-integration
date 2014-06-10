/**
 * Created by Chris on 9/06/14.
 */

var CTCIRunStatus;

if (!CTCIRunStatus) {
    CTCIRunStatus = {};
}

(function () {

    if (typeof CTCIRunStatus.update !== 'function') {
        CTCIRunStatus.update = function ($el, msg) {
            $el.html('<div class="update-nag">' + msg + '</div>');
        }
    }

    if (typeof CTCIRunStatus.success !== 'function') {
        CTCIRunStatus.success = function ($el, msg) {
            $el.html('<div class="updated">' + msg + '</div>');
        }
    }

    if (typeof CTCIRunStatus.error !== 'function') {
        CTCIRunStatus.error = function ($el, msg) {
            $el.html('<div class="error">' + msg + '</div>');
        }
    }

    if (typeof CTCIRunStatus.clear !== 'function') {
        CTCIRunStatus.clear = function ($el) {
            $el.html('');
        }
    }

    if (typeof CTCIRunStatus.setFromObject !== 'function') {
        /**
         * @param $el
         * @param mObj
         * @param ifOK Values 'success' or 'update'. Indicates which box to show if no warnings or errors. The update-mag
         * yellow box, or the updated green 'success' box.
         * @constructor
         */
        CTCIRunStatus.setFromObject = function ($el, mObj, ifOK) {
            // first normalise all the fields
            if ( mObj.message == null ) {
                mObj.message = '';
            }
            if ( mObj.errors == null || mObj.errors == '' ) {
                mObj.errors = 0;
            }
            if ( mObj.error_messages == null || mObj.error_messages == 'null' ) {
                mObj.error_messages = '';
            }
            if ( mObj.warnings == null || mObj.warnings == '' ) {
                mObj.warnings = 0;
            }
            if ( mObj.warning_messages == null || mObj.warning_messages == 'null' ) {
                mObj.warning_messages = '';
            }

            if ( mObj.errors == 0 && mObj.warnings == 0 ) {
                if ( ifOK == 'update' ) {
                    this.update( $el, mObj.message );
                } else {
                    this.success( $el, mObj.message );
                }
            } else if ( mObj.errors > 0 && mObj.warnings == 0 ) {
                // error with message
                if ( mObj.error_messages != '' ) {
                    if ( mObj.errors == 1 ) {
                        this.error(
                            $el, sprintf( ctci_translations.message_1_error_with_message, mObj.message, mObj.error_messages )
                        );
                    } else {
                        this.error(
                            $el, sprintf( ctci_translations.message_x_errors_with_message, mObj.message, mObj.error_messages, mObj.errors )
                        );
                    }
                } else {    // error no message
                    if ( mObj.errors == 1 ) {
                        this.error( $el, sprintf( ctci_translations.message_1_error_no_message, mObj.message ) );
                    } else {
                        this.error( $el, sprintf( ctci_translations.message_x_errors_no_message, mObj.message, mObj.errors ) );
                    }
                }
            } else if ( mObj.errors == 0 && mObj.warnings > 0 ) {
                // warning with message
                if ( mObj.warning_messages != '' ) {
                    if ( mObj.warnings == 1 ) {
                        this.update( $el, sprintf(
                            ctci_translations.message_1_warning_with_message, mObj.message, mObj.warning_messages
                        ) );
                    } else {
                        this.update( $el, sprintf(
                            ctci_translations.message_x_warnings_with_message, mObj.message, mObj.warning_messages, mObj.warnings
                        ) );
                    }
                } else {    // warning(s) with no message
                    if ( mObj.warnings == 1 ) {
                        this.update( $el, sprintf(
                            ctci_translations.message_1_warning_no_message, mObj.message
                        ) );
                    } else {
                        this.update( $el, sprintf(
                            ctci_translations.message_x_warnings_no_message, mObj.message, mObj.warnings
                        ) );
                    }
                }
            } else if ( mObj.errors > 0 && mObj.warnings > 0 ) {
                $msg = mObj.message;
                if ( mObj.error_messages !== '' && mObj.warning_messages !== '' ) {
                    this.error( $el, sprintf(
                        ctci_translations.message_errors_warnings_both_message, mObj.message, mObj.errors, mObj.error_messages,
                        mObj.warnings, mObj.warning_messages
                    ) );
                } else if ( mObj.error_messages !== '' && mObj.warning_messages === '' ) {
                    this.error( $el, sprintf(
                        ctci_translations.message_errors_warnings_error_message, mObj.message, mObj.errors, mObj.error_messages,
                        mObj.warnings
                    ) );
                } else if ( mObj.error_messages === '' && mObj.warning_messages !== '' ) {
                    this.error( $el, sprintf(
                        ctci_translations.message_errors_warnings_warning_message, mObj.message, mObj.errors,
                        mObj.warnings, mObj.warning_messages
                    ) );
                } else if ( mObj.error_messages === '' && mObj.warning_messages === '' ) {
                    this.error( $el, sprintf(
                        ctci_translations.message_errors_warnings_no_message, mObj.message, mObj.errors, mObj.warnings
                    ) );
                }
            }
        }
    }

}());
