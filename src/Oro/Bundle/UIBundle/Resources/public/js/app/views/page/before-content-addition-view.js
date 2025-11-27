import PageRegionView from './../base/page-region-view';

const PageBeforeContentAdditionView = PageRegionView.extend({
    pageItems: ['beforeContentAddition'],

    /**
     * @inheritdoc
     */
    constructor: function PageBeforeContentAdditionView(options) {
        PageBeforeContentAdditionView.__super__.constructor.call(this, options);
    },

    template: function(data) {
        return data.beforeContentAddition;
    },

    render: function() {
        PageBeforeContentAdditionView.__super__.render.call(this);

        if (this.data) {
            this.initLayout();
        }
        return this;
    }
});

export default PageBeforeContentAdditionView;
