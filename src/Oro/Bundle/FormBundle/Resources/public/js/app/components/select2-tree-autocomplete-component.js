import _ from 'underscore';
import Select2AutocompleteView from 'oroform/js/app/views/select2-autocomplete-view';
import Select2AutocompleteComponent from 'oro/select2-autocomplete-component';
import template from 'tpl-loader!oroform/templates/select2-tree-autocomplete-result.html';

const Select2TreeAutocompleteComponent = Select2AutocompleteComponent.extend({
    ViewType: Select2AutocompleteView,

    /**
     * @inheritdoc
     */
    constructor: function Select2TreeAutocompleteComponent(options) {
        Select2TreeAutocompleteComponent.__super__.constructor.call(this, options);
    },

    preConfig: function(config) {
        Select2TreeAutocompleteComponent.__super__.preConfig.call(this, config);

        const propName = config.renderedPropertyName || 'name';
        config.result_template = config.result_template || this.makeItemTemplate(propName, true);
        config.selection_template = config.selection_template || this.makeItemTemplate(propName, false);
        config.containerCssClass = 'select2-tree-autocomplete';
        config.onAfterInit = function(select2Instance) {
            const oldPositionDropdown = select2Instance.positionDropdown;
            select2Instance.positionDropdown = function() {
                this.container.addClass('hide-all-tree-related-ui');
                oldPositionDropdown.call(this);
                this.container.removeClass('hide-all-tree-related-ui');
            };
        };

        return config;
    },

    makeItemTemplate: function(propName, forSelection) {
        const mixData = {
            newKey: 'oro.form.add_new',
            getLabel: function(item, highlight) {
                let label = _.escape(item[propName]);
                if (forSelection) {
                    label = highlight(label);
                }
                return label;
            }
        };

        return function(data) {
            return template(_.extend(Object.create(data), mixData));
        };
    }
});

export default Select2TreeAutocompleteComponent;
