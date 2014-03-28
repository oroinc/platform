/* jshint browser:true */
/*global define*/
define(['jquery', 'underscore'], function ($, _) {
    'use strict';

    return {
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
                canDisable: this.canDisable,
                isEmpty: this.isEmptyValue()
            }));

            this.$(this.criteriaSelector).append($filter);

            this._clickOutsideCriteriaCallback = _.bind(function(e) {
                if (this.popupCriteriaShowed) {
                    this._onClickOutsideCriteria(e);
                }
            }, this);

            $('body').on('click', this._clickOutsideCriteriaCallback);

            this.$el.on('keyup', '.dropdown-menu.filter-criteria', _.bind(function (e) {
                if (e.keyCode === 27) {
                    this._hideCriteria();
                }
            }, this));
        }
    };
});
