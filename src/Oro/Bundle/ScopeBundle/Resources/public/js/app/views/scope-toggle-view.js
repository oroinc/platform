define(function(require) {
    'use strict';

    var ScopeToggleView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export oroscope/js/app/views/scope-toggle-view
     * @extends oroui.app.views.base.View
     * @class oroscope.app.views.ScopeToggleView
     */
    ScopeToggleView = BaseView.extend({
        /**
         * @property {Object}
         */
        options: {
            selectors: {
                useParentScopeSelector: '.parent-scope-use',
                scopesSelector: '.scopes'
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
         * @inheritDoc
         */
        constructor: function ScopeToggleView() {
            ScopeToggleView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = $.extend(true, {}, this.options, options || {});
            this.initLayout().done(_.bind(this.handleLayoutInit, this));
        },

        handleLayoutInit: function() {
            this.$useParentScope = this.$el.find(this.options.selectors.useParentScopeSelector);
            this.$scopeFields = this.$el.find(this.options.selectors.scopesSelector);

            this._initScopes();
            this.$el.on('change', this.$useParentScope, _.bind(this._toggleScopes, this));
        },

        _initScopes: function() {
            if (this.$useParentScope.is(':checked')) {
                this.$scopeFields.hide();
            } else {
                this.$scopeFields.show();
            }
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
