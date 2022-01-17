import BaseView from 'oroui/js/app/views/base/view';

const CellLinkView = BaseView.extend({
    autoRender: true,

    className: 'cell-link',

    tagName: 'a',

    containerMethod: 'prepend',

    optionNames: BaseView.prototype.optionNames.concat(['url']),

    url: null,

    events: {
        click: 'onClick'
    },

    attributes() {
        return {
            'href': this.url,
            'data-include': true
        };
    },

    constructor: function CellLinkView(...args) {
        CellLinkView.__super__.constructor.apply(this, args);
    },

    onClick(e) {
        if (e.which === 1) {
            e.preventDefault();
        }
    }
});

export default CellLinkView;
