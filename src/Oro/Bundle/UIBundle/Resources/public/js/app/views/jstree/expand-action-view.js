define(function(require) {
    'use strict';

    var ExpandActionView;
    var AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');

    ExpandActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'plus-square-o',
            label: __('oro.ui.jstree.actions.expand')
        }),

        onClick: function() {
            var $tree = this.options.$tree;
            var settings = $tree.jstree().settings;
            var autohideNeighbors = settings.autohideNeighbors;

            settings.autohideNeighbors = false;
            var afterOpenAll = _.debounce(function() {
                settings.autohideNeighbors = autohideNeighbors;
                $tree.off('open_all.jstree', afterOpenAll);
            }, 100);

            $tree.on('open_all.jstree', afterOpenAll);

            $tree.jstree('show_all');
            $tree.jstree('open_all');
        }
    });

    return ExpandActionView;
});
