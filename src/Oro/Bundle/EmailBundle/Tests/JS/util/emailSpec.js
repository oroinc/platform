define([
    'oroemail/js/util/email'
], function(emailUtil) {
    'use strict';

    var data = [
        {
            fullEmailAddress: 'john@example.com',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: '<john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: 'John Smith <john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: 'John Smith" <john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: '\'John Smith\' <john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: 'John Smith on behaf <john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: '"john@example.com" <john@example.com>',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: '"john@example.com" <john@example.com> (Contact)',
            pureEmailAddress: 'john@example.com'
        },
        {
            fullEmailAddress: '<john@example.com> (Contact)',
            pureEmailAddress: 'john@example.com'
        }
    ];

    describe('oroemail/js/util/email', function() {
        it('should extract pure email address', function() {
            data.forEach(function(val) {
                expect(emailUtil.extractPureEmailAddress(val.fullEmailAddress)).toEqual(val.pureEmailAddress);
            });
        });
    });
});
