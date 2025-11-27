import AbstractActionView from 'oroui/js/app/views/jstree/abstract-action-view';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';

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

export default CollapseActionView;
