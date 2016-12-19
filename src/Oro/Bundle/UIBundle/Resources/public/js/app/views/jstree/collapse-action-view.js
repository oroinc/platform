define(function(require) {
    'use strict';

    var CollapseActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');

    CollapseActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'minus-square-o',
            label: _.__('oro.ui.jstree.actions.collapse')
        }),

        onClick: function() {
            this.options.$tree.jstree('close_all');
        }
    });

    return CollapseActionView;
});
