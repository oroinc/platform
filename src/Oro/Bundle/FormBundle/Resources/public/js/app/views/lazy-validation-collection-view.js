import BaseView from 'oroui/js/app/views/base/view';

const LazyValidationCollectionView = BaseView.extend({
    events: {
        'change .oro-collection-item[data-validation-ignore]': 'enableValidation'
    },

    constructor: function LazyValidationCollectionView(options) {
        LazyValidationCollectionView.__super__.constructor.call(this, options);
    },

    enableValidation(e) {
        this.$(e.currentTarget).attr('data-validation-ignore', null);
    }
});

export default LazyValidationCollectionView;
