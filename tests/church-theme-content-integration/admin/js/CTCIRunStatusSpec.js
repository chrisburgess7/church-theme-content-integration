/**
 * Created by Chris on 9/06/14.
 */

describe("Update Run Status Suite", function() {

    function makeMsgObj(message, errors, error_messages, warnings, warning_messages) {
        var obj = {};
        obj.message = message;
        obj.errors = errors;
        obj.error_messages = error_messages;
        obj.warnings = warnings;
        obj.warning_messages = warning_messages;
        return obj;
    }

    var $j = jQuery.noConflict();

    beforeEach(function() {
        spyOn(CTCIRunStatus, 'success');
        spyOn(CTCIRunStatus, 'update');
        spyOn(CTCIRunStatus, 'error');
    });

    it("success message correct", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Success message', 0, '', 0, ''), 'success');
        expect(CTCIRunStatus.success).toHaveBeenCalledWith($j, 'Success message');
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("update message correct", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Success message', 0, '', 0, ''), 'update');
        expect(CTCIRunStatus.update).toHaveBeenCalledWith($j, 'Success message');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("1 error with message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Final message', 1, 'An Error message', 0, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error ).toHaveBeenCalledWith($j, 'Final message. Error: An Error message');
    });

    it("2 errors with message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Final message', 2, 'An Error message', 0, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Final message. Error: An Error message (2 errors in total, see log for details)');
    });

    it("1 error no message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Final message', 1, null, 0, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error ).toHaveBeenCalledWith($j, 'Final message. An error has occurred. See log for details.');
    });

    it("3 errors no message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Final message', 3, 'null', 0, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Final message. 3 errors have occurred. See log for details.');
    });

    it("1 warning with message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 0, null, 1, 'A warning message'), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update )
            .toHaveBeenCalledWith($j, 'Run message. Warning: A warning message');
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("3 warnings with message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 0, null, 3, 'A warning message'), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update )
            .toHaveBeenCalledWith($j, 'Run message. Warning: A warning message (3 warnings in total, see log for details)');
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("1 warning no message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 0, null, 1, null), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update )
            .toHaveBeenCalledWith($j, 'Run message. A warning has occurred. See log for details.');
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("2 warnings no message", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 0, null, 2, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update )
            .toHaveBeenCalledWith($j, 'Run message. 2 warnings have occurred. See log for details.');
        expect( CTCIRunStatus.error.calls.count() ).toEqual(0);
    });

    it("1 error, 1 warning with messages", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 1, 'The error message', 1, 'The warning message'), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Run message. 1 error(s) have occurred. Error: The error message. 1 warning(s) have occurred. Warning: The warning message.');
    });

    it("1 error, 2 warnings with warning message only", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 1, '', 2, 'The warning message'), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Run message. 1 error(s) have occurred, see log. 2 warning(s) have occurred. Warning: The warning message.');
    });

    it("2 errors, 1 warning with error message only", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 2, 'The error message', 1, 'null'), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Run message. 2 error(s) have occurred. Error: The error message. 1 warning(s) have occurred, see log.');
    });

    it("1 error, 3 warnings, no messages", function() {
        CTCIRunStatus.setFromObject($j, makeMsgObj('Run message', 1, '', 3, ''), '');
        expect( CTCIRunStatus.success.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.update.calls.count() ).toEqual(0);
        expect( CTCIRunStatus.error )
            .toHaveBeenCalledWith($j, 'Run message. 1 error(s) have occurred, see log. 3 warning(s) have occurred, see log.');
    });
});
