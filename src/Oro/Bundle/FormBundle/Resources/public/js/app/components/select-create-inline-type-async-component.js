import SelectCreateInlineTypeAsyncView from 'oroform/js/app/views/select-create-inline-type-async-view';
import SelectCreateInlineTypeComponent from 'oroform/js/app/components/select-create-inline-type-component';

const SelectCreateInlineTypeAsyncComponent = SelectCreateInlineTypeComponent.extend({
    ViewConstructor: SelectCreateInlineTypeAsyncView,

    /**
     * @inheritdoc
     */
    constructor: function SelectCreateInlineTypeAsyncComponent(options) {
        SelectCreateInlineTypeAsyncComponent.__super__.constructor.call(this, options);
    }
});

export default SelectCreateInlineTypeAsyncComponent;
