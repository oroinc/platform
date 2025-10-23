import BaseComponent from 'oroui/js/app/components/base/component';
import MultiSelectView from 'oroui/js/app/views/multiselect/multiselect-view';
import MultiSelectDropdownView from 'oroui/js/app/views/multiselect/variants/dropdown/multiselect-dropdown-view';

const MultiselectBaseComponent = BaseComponent.extend({
    selectSelector: 'select[multiple]',

    constructor: function MultiselectBaseComponent(...args) {
        MultiselectBaseComponent.__super__.constructor.apply(this, args);
    },

    initialize(options) {
        const {_sourceElement, _subPromises, name, ...viewOptions} = options;

        if (viewOptions.options) {
            Object.assign(viewOptions, {
                el: _sourceElement
            });
        } else {
            const selectElement = _sourceElement.is(this.selectSelector)
                ? _sourceElement
                : _sourceElement.find(this.selectSelector);

            Object.assign(viewOptions, {
                container: _sourceElement,
                containerMethod: 'before',
                selectElement
            });
        }

        this.initView(viewOptions);
    },

    getView({dropdownMode} = {}) {
        if (dropdownMode) {
            return MultiSelectDropdownView;
        }

        return MultiSelectView;
    },

    initView({autoRender = true, ...viewOptions} = {}) {
        const View = this.getView(viewOptions);

        this.view = new View({
            ...viewOptions,
            autoRender
        });
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        this.view.dispose();

        MultiselectBaseComponent.__super__.dispose.call(this);
    }
});

export default MultiselectBaseComponent;
