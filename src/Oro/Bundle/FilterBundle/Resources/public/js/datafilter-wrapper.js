/* jshint browser:true */
/* global define, require */
define(['jquery', 'underscore'], function($, _) {
    'use strict';

    var wrapperTemplate = '';

    var wrapperTemplateSelector = '#filter-wrapper-template';

    var getWrapperTemplate = function () {
        if (!wrapperTemplate) {
            var wrapperTemplateSrc = $(wrapperTemplateSelector).text();
            wrapperTemplate = _.template(wrapperTemplateSrc);
        }
        return wrapperTemplate;
    };

    return {
        /**
         * @property {Boolean}
         */
        popupCriteriaShowed: false,

        _wrap: function ($filter) {
            this.setElement(getWrapperTemplate()({
                label: this.label,
                showLabel: this.showLabel,
                criteriaHint: this._getCriteriaHint(),
                nullLink: this.nullLink,
                canDisable: this.canDisable
            }));

            this.$(this.criteriaSelector).append($filter);

            this._clickOutsideCriteriaCallback = _.bind(function(e) {
                if (this.popupCriteriaShowed) {
                    this._onClickOutsideCriteria(e);
                }
            }, this);

            $('body').on('click', this._clickOutsideCriteriaCallback);
        }
    };
});
