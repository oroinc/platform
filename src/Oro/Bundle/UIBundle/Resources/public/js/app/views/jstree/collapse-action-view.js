define(function(require) {
    'use strict';

    var CollapseActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    CollapseActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'minus-square-o',
            label: __('oro.ui.jstree.actions.collapse')
        }),

        /**
         * @inheritDoc
         */
        constructor: function CollapseActionView() {
            CollapseActionView.__super__.constructor.apply(this, arguments);
        },

        onClick: function() {
            this.options.$tree.jstree('close_all');
        }
    });

    return CollapseActionView;
});
