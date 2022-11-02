define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');

    const KEYBOARD_CODES = require('oroui/js/tools/keyboard-key-codes').default;

    const dataFilterWrapper = {
        /**
         * @property {boolean}
         */
        popupCriteriaShowed: false,

        _getWrapperTemplate: function() {
            if (!this.wrapperTemplate) {
                let wrapperTemplateSrc = '';
                if (this.wrapperTemplateSelector) {
                    wrapperTemplateSrc = $(this.wrapperTemplateSelector).text();
                }
                this.wrapperTemplate = _.template(wrapperTemplateSrc);
            }
            return this.wrapperTemplate;
        },

        _wrap: function($filter) {
            this.setElement(this._getWrapperTemplate()({
                label: this.labelPrefix + this.label,
                showLabel: this.showLabel,
                isEmpty: this.isEmptyValue(),
                renderMode: this.renderMode,
                criteriaHint: this._getCriteriaHint(),
                criteriaClass: this.getCriteriaExtraClass(),
                ...this.getTemplateDataProps()
            }));

            this._appendFilter($filter);

            const events = ['click', 'multiselectbeforeopen', 'show.bs.dropdown', 'clearMenus']
                .map(eventName => eventName + this._eventNamespace())
                .join(' ');

            $(document).off(this._eventNamespace());
            $(document).on(events, e => {
                if (this.popupCriteriaShowed && this.autoClose !== false) {
                    this._onClickOutsideCriteria(e);
                }
            });

            // will be automatically unbound in backbone view's undelegateEvents() method
            this.$el.on(`keydown${this._eventNamespace()}`, '.dropdown-menu.filter-criteria', e => {
                if (e.keyCode === KEYBOARD_CODES.ESCAPE) {
                    this._hideCriteria();
                    this.focusCriteriaToggler();
                }
            });
        },

        /**
         * Removes trace of wrapper from the view
         *  - unbind event listener from body element
         *  - removes properties which belongs to wrapper
         *  - calls original dispose method
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            $(document).off(this._eventNamespace());
            _.each(_.keys(dataFilterWrapper), function(prop) {
                delete this[prop];
            }, this);
            this.constructor.__super__.dispose.call(this);
        },

        /**
         * Returns event's name space
         *
         * @returns {string}
         * @protected
         */
        _eventNamespace: function() {
            return '.delegateEvents' + this.cid;
        },

        _appendFilter: function($filter) {
            this.$(this.criteriaSelector).append($filter);
        },

        /**
         * Close criteria dropdown
         *
         * @returns {*}
         */
        close: function() {
            this._hideCriteria();

            return this;
        }
    };

    return dataFilterWrapper;
});
