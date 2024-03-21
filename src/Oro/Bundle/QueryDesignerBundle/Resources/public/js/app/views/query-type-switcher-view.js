import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroquerydesigner/templates/query-type-switcher.html';

const QueryTypeSwitcherView = BaseView.extend({
    template,

    events: {
        'click .btn': 'onClick'
    },

    listen: {
        'change model': 'render'
    },

    /**
     * @inheritDoc
     */
    constructor: function QueryTypeSwitcherView(...args) {
        QueryTypeSwitcherView.__super__.constructor.call(this, ...args);
    },

    render() {
        QueryTypeSwitcherView.__super__.render.call(this);

        this.$el.addClass('query-type-switcher-container');

        return this;
    },

    onClick() {
        this.trigger('switch');
    }
});

export default QueryTypeSwitcherView;
