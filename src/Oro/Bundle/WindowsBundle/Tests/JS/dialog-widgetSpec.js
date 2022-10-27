define(function(require) {
    'use strict';

    const DialogWidget = require('oro/dialog-widget');

    describe('oro/dialog-widget', function() {
        describe('check focus on dialog content', function() {
            beforeEach(function() {
                this.dialog = new DialogWidget({
                    stateEnabled: false
                });
            });

            afterEach(function() {
                this.dialog.dispose();
            });

            it('first visible input should be focused', function() {
                this.dialog.setContent('<div class="widget-content"><form><input></form></div>');
                this.dialog.focusContent();
                expect(document.activeElement).toEqual(this.dialog.$(':input').get(0));
            });

            it('dialog element should be focused', function() {
                this.dialog.setContent('<div class="widget-content"></div>');
                this.dialog.focusContent();
                expect(document.activeElement).toEqual(this.dialog.$el.parent().get(0));
            });
        });
    });
});
