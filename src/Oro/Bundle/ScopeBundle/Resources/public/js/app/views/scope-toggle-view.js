define(function(require) {
    'use strict';

    const $ = require('jquery');
    const BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroscope/js/app/views/scope-toggle-view
     * @extends oroui.app.views.base.View
     * @class oroscope.app.views.ScopeToggleView
     */
    const ScopeToggleView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useParentScopeSelector: '.parent-scope-use',
                scopesSelector: '.scopes',
                containerSelector: null
            }
        },

        /**
         * @property {jQuery}
         */
        $useParentScope: null,

        /**
         * @property {jQuery}
         */
        $scopeFields: null,

        /**
         * @inheritdoc
         */
        constructor: function ScopeToggleView(options) {
            ScopeToggleView.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(this.handleLayoutInit.bind(this));
        },

        handleLayoutInit: function() {
            const $el = this.options.selectors.containerSelector !== null
                ? this.$el.closest(this.options.selectors.containerSelector)
                : this.$el;
            this.$useParentScope = $el.find(this.options.selectors.useParentScopeSelector);
            this.$scopeFields = $el.find(this.options.selectors.scopesSelector);

            this._toggleScopes();
            $el.on('change', this.$useParentScope, this._toggleScopes.bind(this));
        },

        _toggleScopes: function() {
            if (this.$useParentScope.is(':checked')) {
                this.$scopeFields.hide();
            } else {
                this.$scopeFields.show();
            }
        }
    });

    return ScopeToggleView;
});
