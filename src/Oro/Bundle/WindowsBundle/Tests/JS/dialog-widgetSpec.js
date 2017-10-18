define(function(require) {
    'use strict';

    require('jasmine-jquery');

    var DialogWidget = require('oro/dialog-widget');
    var $ = require('jquery');

    var content = require('text!./Fixture/dialog-widget-content.html');

    describe('oro/dialog-widget', function() {
        describe('check focus on dialog content', function() {
            beforeEach(function() {
                window.setFixtures(content);
                this.$dialog = $('div#dialog');
                this.$input = this.$dialog.find('#first-input');

                this.dialog = new DialogWidget({
                    el: this.$dialog.get(0)
                });
                this.dialog.widget = this.$dialog.find('#widget');
            });

            it('first visible input should be focused', function() {
                this.dialog.focusContent();
                expect(document.activeElement).toEqual(this.$input.get(0));
            });

            it('dialog element should be focused', function() {
                this.$input.hide();
                this.dialog.focusContent();
                expect(document.activeElement).toEqual(this.$dialog.get(0));
            });
        });
    });
});
