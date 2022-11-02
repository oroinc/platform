import BaseView from 'oroui/js/app/views/base/view';

const CellLinkView = BaseView.extend({
    autoRender: true,

    className: 'cell-link',

    tagName: 'a',

    containerMethod: 'prepend',

    optionNames: BaseView.prototype.optionNames.concat(['url']),

    url: null,

    events: {
        click: 'onClick',
        mousemove: 'onMouseMove',
        mousedown: 'onMouseDown',
        mouseup: 'onMouseUp'
    },

    attributes() {
        return {
            'href': this.url,
            'data-include': true,
            'draggable': false
        };
    },

    constructor: function CellLinkView(...args) {
        CellLinkView.__super__.constructor.apply(this, args);
    },

    render() {
        const {padding} = getComputedStyle(this.container[0]);

        this.inner = document.createElement('span');
        this.inner.classList.add('cell-link-inner');
        this.inner.innerHTML = new Array(this.container.text().length).join('&nbsp;');
        this.inner.setAttribute('draggable', false);
        this.inner.style.padding = padding;

        this.$el.css('-moz-user-select', 'auto');
        this.$el.append(this.inner);

        CellLinkView.__super__.render.call(this);
    },

    onMouseDown() {
        this.resetSelection();
        this.pressed = true;
    },

    onMouseUp() {
        this.resetSelection();
        this.pressed = false;
        this.$el.show();
    },

    onMouseMove() {
        if (!this.pressed) {
            return;
        }

        const selection = window.getSelection();

        if (selection.rangeCount) {
            selection.removeAllRanges();
        }

        const textNode = [...this.container[0].childNodes].find(child => child.nodeType === 3);
        selection.collapse(textNode || this.container[0]);
        this.$el.hide();
    },

    resetSelection() {
        if (window.getSelection) {
            if (window.getSelection().empty) {
                window.getSelection().empty();
            } else if (window.getSelection().removeAllRanges) {
                window.getSelection().removeAllRanges();
            }
        }
    },

    onClick(event) {
        if (event.which === 1) {
            event.preventDefault();
        }
    }
});

export default CellLinkView;
