import ButtonView from './button-view';
import ButtonModel from './button-model';
import FieldSelectView from '../views/field-select-view';
import BaseCollection from 'oroui/js/app/models/base/collection';
import BaseCollectionView from 'oroui/js/app/views/base/collection-view';
import template from 'tpl-loader!oroform/templates/expression-editor-extensions/buttons-list.html';

const SidePanelButtonsCollectionView = BaseCollectionView.extend({
    optionNames: BaseCollectionView.prototype.optionNames.concat(['applicableOperations', 'allowedOperations']),

    applicableOperations: null,

    allowedOperations: [],

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
        return item.get('enabled') && item.isAllowed(this.allowedOperations);
    },

    filterCallback(view, included) {
        view.$el.toggleClass('hide', !included);
    },

    initItemView(model) {
        const View = SidePanelButtonsCollectionView.getViewConstructorByType(model.get('type'));

        if (View) {
            return new View({
                autoRender: false,
                model: model,
                ...(model.get('viewOptions') || {})
            });
        }

        return SidePanelButtonsCollectionView.__super__.initItemView.call(this, model);
    }
}, {
    types: {
        button: ButtonView,
        selectField: FieldSelectView
    },

    getViewConstructorByType(type) {
        return SidePanelButtonsCollectionView.types[type] || SidePanelButtonsCollectionView.types.button;
    }
});

export default SidePanelButtonsCollectionView;
