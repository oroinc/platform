import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!oroquerydesigner/templates/query-type-switcher.html';

const QueryTypeSwitcherView = BaseView.extend({

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
    constructor: function QueryTypeSwitcherView(...args) {
        QueryTypeSwitcherView.__super__.constructor.call(this, ...args);
    },

    onClick() {
        this.trigger('switch');
    }
});

export default QueryTypeSwitcherView;
