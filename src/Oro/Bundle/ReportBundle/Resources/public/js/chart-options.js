define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var Util = require('oroentity/js/entity-fields-util');
    require('oroentity/js/fields-loader');
    require('jquery.select2');

    /**
     * @export ororeport/js/chart-options
     * @class  ororeport.ChartOptions
     */
    return {

        /**
         * @property {Object}
         */
        options: {
            childTemplate: '[id^=<%= id %>_]',
            optionsTemplate: '<%= field %>(<%= group %>,<%= name %>,<%= type %>)',
            fieldsLoaderSelector: '[data-ftid=oro_report_form_entityoro_api_querydesigner_fields_entity]',
            fieldsTableIdentifier: 'item-container'
        },

        /**
         * @param {String} id
         * @param {Array} options
         */
        initialize: function(id, options) {
            var self = this;
            this.options = _.extend({}, this.options, options);
            this.childSelectorTemplate = _.template(this.options.childTemplate);
            this.optionsTemplate = _.template(this.options.optionsTemplate);
            this.items = [];

            mediator.on('items-manager:table:reset:' + self.options.fieldsTableIdentifier, function(collection) {
                self.updateCollection(collection);
                self.validateSelect2(id);
            });

            mediator.once('items-manager:table:reset:' + self.options.fieldsTableIdentifier, function() {
                self.initSelect2(id);
                self.initializeListeners(id);
            });
        },

        /**
         * @param {String} id
         */
        initializeListeners: function(id) {
            var self = this;
            mediator.on('items-manager:table:add:' + self.options.fieldsTableIdentifier, function(collection) {
                self.updateCollection(collection);
            });

            mediator.on('items-manager:table:change:' + self.options.fieldsTableIdentifier, function(collection) {
                self.updateCollection(collection);
                self.validateSelect2(id);
            });

            mediator.on('items-manager:table:remove:' + self.options.fieldsTableIdentifier, function(collection) {
                self.updateCollection(collection);
                self.validateSelect2(id);
            });
        },

        /**
         * @param {FieldsCollection} collection
         */
        updateCollection: function(collection) {
            var entity = $(this.options.fieldsLoaderSelector).fieldsLoader('getEntityName');
            var data = $(this.options.fieldsLoaderSelector).fieldsLoader('getFieldsData');
            this.util = new Util(entity, data);
            this.items = collection.clone().removeInvalidModels().toJSON();
        },

        /**
         * @param {String} id
         */
        validateSelect2: function(id) {
            var self = this;
            var $element = $('#' + id);
            var childSelector = this.childSelectorTemplate({id: $element.data('ftid')});
            $element.find(childSelector).each(function() {
                var $input = $(this);
                var value = $input.val();
                if (value) {
                    var name = _.first(value.split('('));
                    if (!_.findWhere(self.items, {name: name})) {
                        if ($input.data('select2')) {
                            $input.inputWidget('val', '');
                        } else {
                            $input.val('');
                        }
                    }
                }
            });
        },

        /**
         * @param {String} id
         */
        initSelect2: function(id) {
            var self = this;
            var $element = $('#' + id);
            var childSelector = this.childSelectorTemplate({id: $element.data('ftid')});
            $element.find(childSelector).each(function() {
                var exclude = $(this).data('type-filter');
                $(this).select2({
                    collapsibleResults: true,
                    placeholder: __('oro.entity.form.choose_entity_field'),
                    data: function() {
                        return self.data(exclude);
                    },
                    initSelection: function(element, callback) {
                        var value = element.val().split('(');
                        var node = _.last(self.util.pathToEntityChain(value[0]));
                        callback({
                            id: value.join('('),
                            text: node.field.label
                        });
                    }
                });
            });
        },

        /**
         * @param {Array} exclude
         */
        data: function(exclude) {
            var util = this.util;
            var data = {
                more: false,
                results: []
            };

            if (!util) {
                return data;
            }

            var optionsTemplate = this.optionsTemplate;

            $.each(this.items, function() {
                var options = this.func;
                var chain = util.pathToEntityChain(this.name).slice(1);
                var entity = chain[chain.length - 1];
                var items = data.results;
                if (!entity || !Util.filterFields([entity.field], exclude).length) {
                    return;
                }
                $.each(chain, function() {
                    var item;
                    var id;
                    if (this.entity) {
                        item = _.findWhere(items, {path: this.path});
                        if (!item) {
                            item = {
                                text: this.field.label,
                                path: this.path,
                                children: []
                            };
                            items.push(item);
                        }
                        items = item.children;
                    } else {
                        if (options) {
                            id = optionsTemplate({
                                field: this.path,
                                group: options.name,
                                name: options.group_name,
                                type: options.group_type
                            });
                        } else {
                            id = this.path;
                        }
                        items.push({
                            text: this.field.label,
                            id: id
                        });
                    }
                });
            });

            return data;
        }
    };
});
