import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';

const DynamicAccessibleButtonView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'proxyEvents', 'proxyElement', 'dataAttrName'
    ]),

    /**
     * @inheritdoc
     */
    autoRender: false,

    /**
     * Events to listen to on a proxyElement
     */
    proxyEvents: null,

    /**
     * Specific element on the DOM to listen to
     */
    proxyElement: null,

    /**
     * Specific 'data-*' attribute to read data from
     */
    dataAttrName: 'id',

    /**
     * @inheritdoc
     */
    constructor: function DynamicAccessibleButtonView(options) {
        this.proxyHandler = this.proxyHandler.bind(this);
        DynamicAccessibleButtonView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        if (typeof this.proxyEvents === 'string') {
            this.proxyEvents = [this.proxyEvents];
        }
        DynamicAccessibleButtonView.__super__.initialize.call(this, options);
    },

    /**
     * @param {Event} e
     */
    proxyHandler(e) {
        if ($(e.target).attr(`data-${this.dataAttrName}`)) {
            this._updateAttributes($(e.target).data(this.dataAttrName));
        }
    },

    /**
     * @inheritdoc
     */
    delegateEvents: function() {
        DynamicAccessibleButtonView.__super__.delegateEvents.call(this);

        if (Array.isArray(this.proxyEvents)) {
            this.proxyEvents.forEach(event => {
                $(this.proxyElement).on(event, this.proxyHandler);
            });
        }

        return this;
    },

    /**
     * @inheritdoc
     */
    undelegateEvents: function() {
        DynamicAccessibleButtonView.__super__.undelegateEvents.call(this);

        if (Array.isArray(this.proxyEvents)) {
            this.proxyEvents.forEach(event => {
                $(this.proxyElement).off(event, this.proxyHandler);
            });
        }

        return this;
    },

    /**
     * @param {string|number|boolean} predicate
     * @private
     */
    _updateAttributes(predicate) {
        let options = this.$el.data('attributes');

        if (!Array.isArray(options)) {
            return;
        }

        options = options.find(obj => obj.id === predicate);

        if (options === void 0) {
            return;
        }

        this.$el.attr('href', options.url);
    }
});

export default DynamicAccessibleButtonView;
