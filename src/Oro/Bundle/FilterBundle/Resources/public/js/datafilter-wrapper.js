define([
    'jquery',
    'underscore'
], function($, _) {
    'use strict';

    var dataFilterWrapper;

    dataFilterWrapper = {
        /**
         * @property {boolean}
         */
        popupCriteriaShowed: false,

        _getWrapperTemplate: function() {
            if (!this.wrapperTemplate) {
                var wrapperTemplateSrc = '';
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
                criteriaHint: this._getCriteriaHint(),
                canDisable: this.canDisable,
                isEmpty: this.isEmptyValue(),
                renderMode: this.renderMode
            }));

            this._appendFilter($filter);

            var events = ['click', 'show.bs.dropdown'].map(function(eventName) {
                return eventName + this._eventNamespace();
            }.bind(this)).join(' ');

            $(document).on(events, _.bind(function(e) {
                if (this.popupCriteriaShowed) {
                    this._onClickOutsideCriteria(e);
                }
            }, this));

            // will be automatically unbound in backbone view's undelegateEvents() method
            this.$el.on('keyup' + this._eventNamespace(), '.dropdown-menu.filter-criteria', _.bind(function(e) {
                if (e.keyCode === 27) {
                    this._hideCriteria();
                }
            }, this));
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
