import ButtonView from './button-view';
import ButtonModel from './button-model';
import BaseCollection from 'oroui/js/app/models/base/collection';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import template from 'tpl-loader!oroform/templates/expression-editor-extensions/buttons-list.html';

const SidePanelButtonsCollectionView = BaseCollectionView.extend({
    /**
     * @inheritdoc
     */
    itemView: ButtonView,

    template,

    /**
     * @inheritdoc
     */
    listSelector: '[data-role="buttons"]',

    /**
     * @inheritdoc
     */
    constructor: function SidePanelButtonsCollectionView(options) {
        if (!options.operationButtons) {
            throw new Error('"operationButtons" option is required');
        }

        this.collection = new BaseCollection(options.operationButtons, {
            model: ButtonModel,
            comparator: 'order'
        });

        SidePanelButtonsCollectionView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    filterer: function(item) {
        return item.get('enabled');
    }
});

export default SidePanelButtonsCollectionView;
