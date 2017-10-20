define([
    'jquery',
    'underscore',
    'oroui/js/messenger'
], function($, _, messenger) {
    'use strict';

    var stringsWithNoFlashTags = [
        'Your <a href="/customer/rfp/view/49">Request For Quote</a> has been successfully resubmitted.',
        '<p>Your <a href="/customer/rfp/view/49">Request For Quote</a> has been successfully resubmitted.</p>'
    ];

    var stringsWithoutNoFlashTags = [
        'Your has been <span>successfully</span> resubmitted.',
        '<p>Your has been successfully resubmitted.</p>'
    ];

    describe('oroui/js/messenger', function() {
        it('Messenger _containsNoFlashTags method', function() {
            _.each(stringsWithNoFlashTags, function(string) {
                expect(messenger._containsNoFlashTags(string)).toEqual(true);
            });

            _.each(stringsWithoutNoFlashTags, function(string) {
                expect(messenger._containsNoFlashTags(string)).toEqual(false);
            });
        });
    });
});
