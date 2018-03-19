define(function(require) {
    'use strict';

    var QueryTypeSwitcherView;
    var BaseView = require('oroui/js/app/views/base/view');
    var template = require('tpl!oroquerydesigner/templates/query-type-switcher.html');

    QueryTypeSwitcherView = BaseView.extend({

        template: template,

        events: {
            'click .btn': 'onClick'
        },

        listen: {
            'change model': 'render'
        },

        /**
         * @inheritDoc
         */
        constructor: function QueryTypeSwitcherView() {
            QueryTypeSwitcherView.__super__.constructor.apply(this, arguments);
        },

        onClick: function() {
            this.trigger('switch');
        }
    });

    return QueryTypeSwitcherView;
});

