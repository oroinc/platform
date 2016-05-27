define(function(require) {
    'use strict';

    var Select2AutocompleteComponent;
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    var Select2Component = require('oro/select2-component');

    Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView,
        preConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.preConfig.apply(this, arguments);
            if (config.allowCreateNew) {
                var propName = config.renderedPropertyName || 'name';
                config.result_template = config.result_template || this.makeItemTemplate(propName, false);
                config.selection_template = config.selection_template || this.makeItemTemplate(propName, true);
            }
            return config;
        },

        setConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.setConfig.apply(this, arguments);
            /* 'allowCreateNew' option says to select2 to propose to select new item created with value in search field
             */
            if (config.allowCreateNew) {
                /* 'renderedPropertyName' option helps to select create a new data item with proper field name
                 *  to be rendered properly in the item template
                 */
                var propName = config.renderedPropertyName || 'name';
                config.createSearchChoice = function(value, results) {
                    return _.object([['id', null], [propName, value]]);
                };
                /* In case we create new items we can't use plain id in input value because a new item hasn't it yet
                 * So value is a JSON with value property containing user input text, like {value: "My new item"}
                 */
                config.id = function(e) {
                    return e.id !== null ? e.id : JSON.stringify({value: e[propName]});
                };
            }
            return config;
        },

        makeItemTemplate: function(propName, forSelection) {
            var labelTpl = '_.escape(' + propName + ')';
            if (forSelection) {
                labelTpl = 'highlight(' + labelTpl + ')';
            }
            return '<%= ' + labelTpl + ' %><% if (id === null) { %>' +
                '<span class="select2__result-entry-info"> (' + __('oro.form.new') + ') </span><% } %>';
        }
    });

    return Select2AutocompleteComponent;
});
