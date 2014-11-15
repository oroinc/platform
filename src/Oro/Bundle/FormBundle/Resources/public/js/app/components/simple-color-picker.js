/*jslint nomen: true*/
/*global define*/
define(['underscore', 'oroform/js/app/components/base-simple-color-picker'
    ], function (_, BaseSimpleColorPicker) {
    'use strict';

    var SimpleColorPicker = BaseSimpleColorPicker.extend({
        /**
         * @constructor
         * @param {object} options
         */
        initialize: function (options) {
            SimpleColorPicker.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        _getSimpleColorPickerOptions: function (options) {
            return _.omit(SimpleColorPicker.__super__._getSimpleColorPickerOptions.call(this, options),
                    ['custom_color']
                );
        },

        /**
         * @inheritDoc
         */
        _getPickerOptions: function (options) {
            return SimpleColorPicker.__super__._getPickerOptions.call(this, options.custom_color);
        },

        /**
         * @inheritDoc
         */
        _getPicker: function () {
            return this.$parent.find('span.custom-color');
        }
    });

    return SimpleColorPicker;
});
