import _ from 'underscore';
import messenger from 'oroui/js/messenger';


const stringsWithNoFlashTags = [
    'Your <a href="/customer/rfp/view/49">Request For Quote</a> has been successfully resubmitted.',
    '<p>Your <a href="/customer/rfp/view/49">Request For Quote</a> has been successfully resubmitted.</p>'
];

const stringsWithoutNoFlashTags = [
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
