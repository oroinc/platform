import BaseView from 'oroui/js/app/views/base/view';

const BackdropView = BaseView.extend({
    optionNames: BaseView.prototype.optionNames.concat(['onClickCallback']),

    autoRender: true,

    className: 'backdrop-view',

    events: {
        click: 'onClick'
    },

    onClickCallback: null,

    constructor: function BackdropView(...args) {
        BackdropView.__super__.constructor.apply(this, args);
    },

    onClick() {
        if (typeof this.onClickCallback === 'function') {
            this.onClickCallback();
        }
    },

    show() {
        this.container[this.containerMethod](this.$el);
        setTimeout(() => this.$el.addClass('show'));
    },

    hide() {
        if (this.$el && this.$el.hasClass('show')) {
            this.$el.removeClass('show');

            if (parseFloat(this.$el.css('transition-duration')) !== 0 && this.$el.is(':visible')) {
                this.$el.one(`transitionend${this.eventNamespace()}`, () => {
                    this.$el.detach();
                });
            } else {
                this.$el.detach();
            }
        }
    },

    toggle(state) {
        state ? this.show() : this.hide();
    },

    isOpen() {
        return this.$el.hasClass('show');
    },

    remove() {
        this.$el.off(this.eventNamespace());
        this.$el.remove();
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.remove();
        BackdropView.__super__.dispose.call(this);
    }
});

export default BackdropView;
