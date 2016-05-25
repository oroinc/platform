define(function(require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Util = require('./entity-fields-util');
    require('jquery-ui');
    require('jquery.select2');

    $.widget('oroentity.fieldChoice', {
        options: {
            entity: null,
            data: {},
            dataFilter: function(entityName, entityFields) {
                return entityFields;
            },
            select2: {
                pageableResults: true,
                dropdownAutoWidth: true
            },
            /*
             * Array of rule objects or strings that will be used for entries filtering
             * examples:
             *      ['relation_type'] - will exclude all entries that has 'relation_type' key (means relational fields)
             *      [{name: 'id'}]    - will exclude all entries that has property "name" equals to "id"
             */
            exclude: [],
            fieldsLoaderSelector: ''
        },

        _create: function() {
            this._on({
                change: function(e) {
                    if (e.added) {
                        this.element.trigger('changed', e.added.id);
                    }
                }
            });

            this.util = new Util(this.options.entity, this.options.data);
            this._bindFieldsLoader();
        },

        _init: function() {
            var instance;
            var select2Options;
            var self = this;

            this._processSelect2Options();
            this.updateData(this.options.entity, this.options.data);

            select2Options = $.extend({
                initSelection: function(element, callback) {
                    instance = element.data('select2');
                    var opts = instance.opts;
                    var id = element.val();
                    var match = null;
                    var chain;
                    try {
                        chain = self.util.pathToEntityChain(id, true);
                        instance.pagePath = chain[chain.length - 1].basePath;
                    } catch (e) {
                        instance.pagePath = '';
                    }
                    opts.query({
                        matcher: function(term, text, el) {
                            var isMatch = id === opts.id(el);
                            if (isMatch) {
                                match = el;
                            }
                            return isMatch;
                        },
                        callback: !$.isFunction(callback) ? $.noop : function() {
                            callback(match);
                        }
                    });
                },
                id: function(result) {
                    return result.id !== void 0 ? result.id : result.pagePath;
                },
                data: function() {
                    var pagePath = (instance && instance.pagePath) || '';
                    var results = self._select2Data(pagePath);
                    return {
                        more: false,
                        pagePath: pagePath,
                        results: results
                    };
                },
                formatBreadcrumbItem: function(item) {
                    var label;
                    label = item.field ? item.field.label : item.entity.label;
                    return label;
                },
                breadcrumbs: function(pagePath) {
                    var chain = self.util.pathToEntityChain(pagePath, true);
                    $.each(chain, function(i, item) {
                        item.pagePath = item.basePath;
                    });
                    return chain;
                }
            }, this.options.select2);

            this.element.select2(select2Options);
            instance = this.element.data('select2');
        },

        _setOption: function(key, value) {
            if ($.isPlainObject(value)) {
                $.extend(this.options[key], value);
            } else {
                this._super(key, value);
            }
            return this;
        },

        _getCreateOptions: function() {
            return $.extend(true, {}, this.options);
        },

        _processSelect2Options: function() {
            var template;
            var options = this.options.select2;

            if (options.formatSelectionTemplate) {
                template = _.template(options.formatSelectionTemplate);
                options.formatSelection = $.proxy(function(item) {
                    var result;
                    if (item !== null) {
                        result = this.formatChoice(item.id, template);
                    }
                    return result;
                }, this);
            }
        },

        _bindFieldsLoader: function() {
            if (!this.options.fieldsLoaderSelector) {
                return;
            }
            this.$fieldsLoader = $(this.options.fieldsLoaderSelector);
            this._on(this.$fieldsLoader, {
                fieldsloaderupdate: function(e, data) {
                    this.setValue('');
                    this.updateData($(e.target).val(), data);
                }
            });
            this.updateData(this.$fieldsLoader.val(), this.$fieldsLoader.data('fields'));
        },

        updateData: function(entity, data) {
            data = data || {};
            this.options.entity = entity;
            this.options.data = data;

            this.element
                .data('entity', entity)
                .data('data', data);

            this.util.init(entity, data);
        },

        setValue: function(value) {
            this.element.inputWidget('val', value, true);
        },

        formatChoice: function(value, template) {
            var data;
            if (value) {
                try {
                    data = this.util.pathToEntityChain(value);
                } catch (e) {}
            }
            return data ? template(data) : value;
        },

        splitFieldId: function(fieldId) {
            return this.util.pathToEntityChain(fieldId);
        },

        getApplicableConditions: function(fieldId) {
            return this.util.getApplicableConditions(fieldId);
        },

        /**
         *
         * @param {string} path
         * @returns {Array}
         * @private
         */
        _select2Data: function(path) {
            var fields = [];
            var relations = [];
            var results = [];
            var chain;
            var entityName;
            var entityFields;
            var entityData = this.options.data;
            var util = this.util;
            if ($.isEmptyObject(entityData)) {
                return results;
            }

            try {
                chain = this.util.pathToEntityChain(path, true);
            } catch (e) {
                return results;
            }

            entityName = chain[chain.length - 1].entity.name;
            entityData = entityData[entityName];
            entityFields = this.options.dataFilter.call(this, entityName, entityData.fields);

            if (!_.isEmpty(this.options.exclude)) {
                entityFields = Util.filterFields(entityFields, this.options.exclude);
            }

            $.each(entityFields, function() {
                var field = this;
                var chainItem = {field: field};
                var item = {
                    id: util.entityChainToPath(chain.concat(chainItem)),
                    text: field.label
                };
                if (field.related_entity) {
                    chainItem.entity = field.related_entity;
                    item.pagePath = util.entityChainToPath(chain.concat(chainItem));
                    item.related_entity = field.related_entity;
                    delete item.id;
                    relations.push(item);
                } else {
                    fields.push(item);
                }
            });

            if (!_.isEmpty(fields)) {
                results.push({
                    text: __('oro.entity.field_choice.fields'),
                    children: fields
                });
            }

            if (!_.isEmpty(relations)) {
                results.push({
                    text: __('oro.entity.field_choice.relations'),
                    children: relations
                });
            }

            return results;
        }
    });

    return $;
});
