define(function (require) {
    'use strict';

    var __ = require('orotranslation/js/translator');
    var Select2TreeAutocompleteComponent;
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    Select2TreeAutocompleteComponent = Select2AutocompleteComponent.extend({
        ViewType: Select2AutocompleteView,
        preConfig: function(config) {
            config = Select2TreeAutocompleteComponent.__super__.preConfig.apply(this, arguments);

            var propName = config.renderedPropertyName || 'name';
            config.result_template = config.result_template || this.makeItemTemplate(propName, false);
            config.selection_template = config.selection_template || this.makeItemTemplate(propName, true);
            config.className = 'select2-tree-autocomplete';
            config.onAfterInit = function (select2Instance) {
                var oldPositionDropdown = select2Instance.positionDropdown;
                select2Instance.positionDropdown = function () {
                    this.container.addClass('hide-all-tree-related-ui');
                    oldPositionDropdown.apply(this, arguments);
                    this.container.removeClass('hide-all-tree-related-ui');
                };
            };
            return config;
        },

        makeItemTemplate: function(propName, forSelection) {
            var labelTpl = '_.escape(currentItem[propName])';
            if (forSelection) {
                labelTpl = 'highlight(' + labelTpl + ')';
            }

            var templateHeader =
                '<% var propName = "' + propName + '";' +
                'function getLabel(currentItem){return ' + labelTpl + '} %>';

            var templateBody = require('text!oroform/templates/select2-tree-autocomplete-result.html');

            var templateFooter =
                '<% if (id === null) { %>' +
                '<span class="select2__result-entry-info"> (' + __('oro.form.new') + ') </span><% } %>';

            return templateHeader + templateBody + templateFooter;
        }
    });

    return Select2TreeAutocompleteComponent;
});
