define(function(require) {
    'use strict';

    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const Select2AutocompleteView = require('oroform/js/app/views/select2-autocomplete-view');
    const Select2Component = require('oro/select2-component');

    const Select2AutocompleteComponent = Select2Component.extend({
        ViewType: Select2AutocompleteView,

        /**
         * @inheritdoc
         */
        constructor: function Select2AutocompleteComponent(options) {
            Select2AutocompleteComponent.__super__.constructor.call(this, options);
        },

        preConfig: function(config) {
            Select2AutocompleteComponent.__super__.preConfig.call(this, config);
            if (config.allowCreateNew) {
                const propName = config.renderedPropertyName || 'name';
                config.result_template = config.result_template || this.makeItemTemplate(propName, true);
                config.selection_template = config.selection_template || this.makeItemTemplate(propName, false);
            }
            return config;
        },

        setConfig: function(config) {
            config = Select2AutocompleteComponent.__super__.setConfig.call(this, config);
            /* 'allowCreateNew' option says to select2 to propose to select new item created with value in search field
             */
            if (config.allowCreateNew) {
                /* 'renderedPropertyName' option helps to select create a new data item with proper field name
                 *  to be rendered properly in the item template
                 */
                const propName = config.renderedPropertyName || 'name';
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
            let labelTpl = '_.escape(' + propName + ')';
            if (forSelection) {
                labelTpl = 'highlight(' + labelTpl + ')';
            }
            return '<%= ' + labelTpl + ' %><% if (id === null) { %>' +
                '<span class="select2__result-entry-info"> (' + __('oro.form.add_new') + ') </span><% } %>';
        }
    }, {
        setSelect2ValueById: function(self, config, element, callback, id) {
            if (config.allowCreateNew) {
                // when it's needed to show not stored new item in the control (e.g. after failed save of form) it
                // processes id and extracts user input value
                let json;
                try {
                    json = JSON.parse(id);
                } catch (ex) {}
                if (_.isObject(json) && 'value' in json) {
                    const propName = config.renderedPropertyName || 'name';
                    const item = {id: null};
                    item[propName] = json.value;
                    self.handleResults(self, config, callback, [item]);
                    return;
                }
            }
            Select2AutocompleteComponent.__super__.constructor.setSelect2ValueById(self, config, element, callback, id);
        }
    });

    return Select2AutocompleteComponent;
});
