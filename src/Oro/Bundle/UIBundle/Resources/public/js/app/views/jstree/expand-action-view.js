define(function(require) {
    'use strict';

    const AbstractActionView = require('oroui/js/app/views/jstree/abstract-action-view');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');

    const ExpandActionView = AbstractActionView.extend({
        options: _.extend({}, AbstractActionView.prototype.options, {
            icon: 'plus-square-o',
            label: __('oro.ui.jstree.actions.expand')
        }),

        /**
         * @inheritdoc
         */
        constructor: function ExpandActionView(options) {
            ExpandActionView.__super__.constructor.call(this, options);
        },

        onClick: function() {
            const $tree = this.options.$tree;
            const settings = $tree.jstree().settings;
            const autohideNeighbors = settings.autohideNeighbors;

            settings.autohideNeighbors = false;
            const afterOpenAll = _.debounce(function() {
                settings.autohideNeighbors = autohideNeighbors;
                $tree.off('open_all.jstree', afterOpenAll);
            }, 100);

            $tree.on('open_all.jstree', afterOpenAll);

            $tree.jstree('open_all');
        }
    });

    return ExpandActionView;
});
