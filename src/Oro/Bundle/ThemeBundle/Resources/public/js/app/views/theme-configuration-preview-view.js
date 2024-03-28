define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    const ThemeConfigurationChangePreview = BaseView.extend({
        events: {
            'change [data-role="change-preview"]': 'onPreviewChange'
        },

        /**
         * @inheritdoc
         */
        constructor: function ThemeConfigurationChangePreview(options) {
            ThemeConfigurationChangePreview.__super__.constructor.call(this, options);
        },

        onPreviewChange: function(e) {
            const key = this._getTargetData(e, 'previewKey');
            const previewDefault = this._getTargetData(e, 'previewDefault');
            const value = this._getInputValue(e) || previewDefault;
            const previewSrc = this._getTargetData(e, 'preview' + value.charAt(0).toUpperCase() + value.slice(1));

            const imageSelector = 'img[data-preview-image="' + key + '"]';
            const $imageTarget = $(imageSelector);

            $imageTarget.attr('src', '/' + previewSrc);
        },

        _getTargetData: function(e, dataKey) {
            const $target = $(e.target);
            const $currentTarget = $(e.currentTarget);

            return typeof $target.data(dataKey) === 'undefined' ? $currentTarget.data(dataKey) : $target.data(dataKey);
        },

        _getInputValue: function(e) {
            const $target = $(e.target);
            switch ($target, $target.attr('type')) {
                case 'radio':
                    let checked = null;
                    $target.each(function() {
                        if ($(this).is(':checked')) {
                            checked = $(this).val();
                        }
                    });

                    return checked;
                case 'checkbox':
                    return $target.is(':checked') ? 'checked' : 'unchecked';
                default:
                    return $target.val();
            }
        }
    });

    return ThemeConfigurationChangePreview;
});
