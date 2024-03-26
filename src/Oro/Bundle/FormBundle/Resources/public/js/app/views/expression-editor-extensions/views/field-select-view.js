import __ from 'orotranslation/js/translator';
import BaseView from 'oroui/js/app/views/base/view';
import FieldChoiceEntityChainView from './field-choice-entity-chain-view';
import {updateState} from '../side-panel/default-operations';

const FieldSelectView = BaseView.extend({
    optionNames: ['rootEntityClassName', 'entityStructureDataProvider', 'supportedNames', 'dataSourceNames'],

    className: 'cm-field-select',

    constructor: function FieldSelectView(...args) {
        FieldSelectView.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        FieldSelectView.__super__.initialize.call(this, options);
        this.render();
        this.listenTo(this.entityStructureDataProvider, 'root-entity-change', this.render);
    },

    render() {
        if (!this.choiceInput) {
            this.choiceInput = document.createElement('input');
            this.$el.append(this.choiceInput);
        }

        this.subview('fieldChoice', new FieldChoiceEntityChainView({
            autoRender: true,
            el: this.choiceInput,
            entityDataProvider: this.entityStructureDataProvider,
            entity: this.entityStructureDataProvider.rootEntityClassName,
            supportedNames: this.supportedNames,
            dataSourceNames: this.dataSourceNames,
            handler: this.handler.bind(this),
            select2: {
                placeholder: __('oro.form.expression_editor.fields.select_placeholder'),
                dropdownCssClass: 'cm-select-field-dropdown'
            }
        }));
    },

    handler(str) {
        const cm = this.model.get('codeMirror');

        updateState(cm, str + ' ');
        setTimeout(() => cm.focus());
    }
});

export default FieldSelectView;
