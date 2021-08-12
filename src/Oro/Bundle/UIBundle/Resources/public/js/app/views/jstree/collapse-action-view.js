define(function(require) {
    'use strict';

    const AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');

    const CollapseActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'minus-square-o',
            label: __('oro.ui.jstree.actions.collapse')
        }),

        /**
         * @inheritdoc
         */
        constructor: function CollapseActionView(options) {
            CollapseActionView.__super__.constructor.call(this, options);
        },

        onClick: function() {
            this.options.$tree.jstree('close_all');
        }
    });

    return CollapseActionView;
});
