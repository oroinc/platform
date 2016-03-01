define(['underscore', 'oroform/js/app/views/base-simple-color-picker-view'
    ], function(_, BaseSimpleColorPickerView) {
    'use strict';

    var SimpleColorPickerView = BaseSimpleColorPickerView.extend({
        /**
         * @constructor
         * @param {object} options
         */
        initialize: function(options) {
            SimpleColorPickerView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritDoc
         */
        _processOptions: function(options) {
            var selectedVal = this.$el.val();
            var selectedIndex = null;
            var customIndex = null;

            SimpleColorPickerView.__super__._processOptions.call(this, options);

            // set custom color
            _.each(options.data, function(value, index) {
                if (value.class) {
                    if (value.class === 'custom-color') {
                        customIndex = index;
                    }
                } else if (selectedVal && value.id === selectedVal) {
                    selectedIndex = index;
                }
            });
            if (customIndex !== null) {
                options.data[customIndex].id = selectedVal && selectedIndex === null ? selectedVal : '#FFFFFF';
            }
        },

        /**
         * @inheritDoc
         */
        _getSimpleColorPickerOptions: function(options) {
            options = SimpleColorPickerView.__super__._getSimpleColorPickerOptions.call(this, options);
            return _.defaults(_.omit(options, ['custom_color']), {
                emptyColor: '#FFFFFF'
            });
        },

        /**
         * @inheritDoc
         */
        _getPickerOptions: function(options) {
            return SimpleColorPickerView.__super__._getPickerOptions.call(this, options.custom_color);
        },

        /**
         * @inheritDoc
         */
        _getPicker: function() {
            return this.$parent.find('span.custom-color');
        }
    });

    return SimpleColorPickerView;
});
