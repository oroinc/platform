define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Select2TreeAutocompleteComponent;
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2TreeAutocompleteComponent = Select2AutocompleteComponent.extend({
        ViewType: Select2AutocompleteView,
        preConfig: function(config) {
            config = Select2TreeAutocompleteComponent.__super__.preConfig.apply(this, arguments);

            var propName = config.renderedPropertyName || 'name';
            config.result_template = config.result_template || this.makeItemTemplate(propName, true);
            config.selection_template = config.selection_template || this.makeItemTemplate(propName, false);
            config.className = 'select2-tree-autocomplete';
            config.onAfterInit = function(select2Instance) {
                var oldPositionDropdown = select2Instance.positionDropdown;
                select2Instance.positionDropdown = function() {
                    this.container.addClass('hide-all-tree-related-ui');
                    oldPositionDropdown.apply(this, arguments);
                    this.container.removeClass('hide-all-tree-related-ui');
                };
            };
            return config;
        },

        makeItemTemplate: function(propName, forSelection) {

            var template = require('tpl!oroform/templates/select2-tree-autocomplete-result.html');

            var mixData = {
                newKey: 'oro.form.new',
                getLabel: function(item, highlight) {
                    var label = _.escape(item[propName]);
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

    return Select2TreeAutocompleteComponent;
});
