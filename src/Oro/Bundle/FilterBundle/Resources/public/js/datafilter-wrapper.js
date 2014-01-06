/* jshint browser:true */
/* global define, require */
define(['jquery', 'underscore'], function($, _) {
    'use strict';

    return {
        wrapperTemplate: '',

        /**
         * @property {Boolean}
         */
        popupCriteriaShowed: false,

        _getWrapperTemplate: function () {
            if (!this.wrapperTemplate) {
                var wrapperTemplateSrc = $(this.wrapperTemplateSelector).text();
                this.wrapperTemplate = _.template(wrapperTemplateSrc);
            }
            return this.wrapperTemplate;
        },

        _wrap: function ($filter) {
            this.setElement(this._getWrapperTemplate()({
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
