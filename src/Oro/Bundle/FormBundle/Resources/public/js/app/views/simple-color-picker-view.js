define(['underscore', 'oroform/js/app/views/base-simple-color-picker-view'
], function(_, BaseSimpleColorPickerView) {
    'use strict';

    const SimpleColorPickerView = BaseSimpleColorPickerView.extend({
        /**
         * @inheritdoc
         */
        constructor: function SimpleColorPickerView(options) {
            SimpleColorPickerView.__super__.constructor.call(this, options);
        },

        /**
         * @constructor
         * @param {object} options
         */
        initialize: function(options) {
            SimpleColorPickerView.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        _processOptions: function(options) {
            SimpleColorPickerView.__super__._processOptions.call(this, options);

            const selectedVal = this.$el.val();
            const customIndex = _.findIndex(options.data, function(item) {
                return item.class === 'custom-color';
            });

            if (customIndex !== -1) {
                if (_.isMobile()) {
                    if (customIndex > 0 && _.isEmpty(options.data[customIndex - 1])) {
                        options.data.splice(customIndex - 1, 2);
                    } else {
                        options.data.splice(customIndex, 1);
                    }
                } else {
                    // set custom color
                    const selectedIndex = _.findIndex(options.data, function(item) {
                        return item.id === selectedVal;
                    });

                    options.data[customIndex].id = selectedVal && selectedIndex === -1 ? selectedVal : '#FFFFFF';
                }
            }
        },

        /**
         * @inheritdoc
         */
        _getSimpleColorPickerOptions: function(options) {
            options = SimpleColorPickerView.__super__._getSimpleColorPickerOptions.call(this, options);
            return _.defaults(_.omit(options, ['custom_color']), {
                emptyColor: '#FFFFFF'
            });
        },

        /**
         * @inheritdoc
         */
        _getPickerOptions: function(options) {
            return SimpleColorPickerView.__super__._getPickerOptions.call(this, options.custom_color);
        },

        /**
         * @inheritdoc
         */
        _getPicker: function() {
            return this.$parent.find('span.custom-color');
        }
    });

    return SimpleColorPickerView;
});
