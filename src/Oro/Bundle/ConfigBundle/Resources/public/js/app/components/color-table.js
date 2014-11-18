/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroform/js/app/components/color-table'
    ], function (_, BaseColorTable) {
    'use strict';

    var ColorTable = BaseColorTable.extend({
        /**
         * @constructor
         * @param {object} options
         */
        initialize: function (options) {
            ColorTable.__super__.initialize.call(this, options);

            // find 'Use Default' checkbox
            this.$useDefault = this.$element.closest('.control-group')
                .find(this.$element.attr('id').replace(/(.+)(_value)$/, '#$1_use_parent_scope$2'));
            // set initial state of a color picker
            this._enable(!this.$useDefault.is(":checked"));
            // monitor changing of 'Use Default' checkbox to set appropriate state of a color picker
            this.$useDefault.on('change' + '.' + this.cid, _.bind(function (e) {
                this._enable(!this.$useDefault.is(":checked"));
            }, this));
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (!this.disposed && this.$useDefault) {
                this.$useDefault.off('.' + this.cid);
            }
            ColorTable.__super__.dispose.call(this);
        },

        _enable: function (enabled) {
            var colors = this.$parent.find('span.color');
            if (enabled) {
                colors.removeAttr('data-disabled');
                colors.attr('tabindex', '0');
            } else {
                colors.attr('data-disabled', '');
                colors.removeAttr('tabindex');
            }
        }
    });

    return ColorTable;
});
