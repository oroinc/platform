define(function(require) {
    'use strict';

    var FieldChoiceView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Util = require('oroentity/js/entity-fields-util');
    var BaseView = require('oroui/js/app/views/base/view');
    require('jquery-ui');
    require('jquery.select2');
    require('oroui/js/input-widget-manager');

    FieldChoiceView = BaseView.extend({
        defaultOptions: {
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
            /*
             * Format same as exclude option
             */
            include: []
        },

        callbacks: {
            select2ResultsCallback: null,
            applicableConditionsCallback: null,
            dataFilter: null
        },

        events: {
            change: 'onChange'
        },

        initialize: function(options) {
            // @TODO: remove fetching data from a DOM element after EntitiesFieldsDataProvider is implementing
            if (!('data' in options) && 'fieldsLoaderSelector' in options) {
                options.data = $(options.fieldsLoaderSelector).data('fields') || this.defaultOptions.data;
            }

            options = _.defaults({}, options, this.defaultOptions);
            _.extend(this, _.pick(options, _.without(_.keys(this.defaultOptions), 'select2')));
            this.callbacks = _.pick(options, _.keys(this.callbacks));
            this.select2Options = this._prepareSelect2Options(options);
            this.util = new Util(this.entity, this.data);
            FieldChoiceView.__super__.initialize.call(this, options);
        },

        onChange: function(e) {
            var selectedItem = e.added || this.$el.inputWidget('data');
            this.trigger('change', selectedItem);
        },

        render: function() {
            var instance;
            var select2Options;

            select2Options = $.extend({
                initSelection: function(element, callback) {
                    instance = element.data('select2');
                    var opts = instance.opts;
                    var id = element.val();
                    var match = null;
                    var chain;
                    try {
                        chain = this.util.pathToEntityChain(id, true);
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
                }.bind(this),
                id: function(result) {
                    return result.id !== void 0 ? result.id : result.pagePath;
                },
                data: function() {
                    instance = this.$el.data('select2');
                    var pagePath = (instance && instance.pagePath) || '';
                    var results = this._select2Data(pagePath);
                    if (_.isFunction(this.callbacks.select2ResultsCallback)) {
                        results = this.callbacks.select2ResultsCallback(results);
                    }
                    return {
                        more: false,
                        pagePath: pagePath,
                        results: results
                    };
                }.bind(this),
                formatBreadcrumbItem: function(item) {
                    var label;
                    label = item.field ? item.field.label : item.entity.label;
                    return label;
                },
                breadcrumbs: function(pagePath) {
                    var chain = this.util.pathToEntityChain(pagePath, true);
                    $.each(chain, function(i, item) {
                        item.pagePath = item.basePath;
                    });
                    return chain;
                }.bind(this)
            }, this.select2Options);

            this.$el.inputWidget('create', 'select2', {initializeOptions: select2Options});
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.$el.data('select2').destroy();
            FieldChoiceView.__super__.dispose.call(this);
        },

        _prepareSelect2Options: function(options) {
            var template;
            var select2Opts = _.clone(options.select2);

            if (select2Opts.formatSelectionTemplate) {
                template = select2Opts.formatSelectionTemplate;
            } else if (select2Opts.formatSelectionTemplateSelector) {
                template = $(select2Opts.formatSelectionTemplateSelector).text();
            }

            if (template) {
                if (!_.isFunction(template)) {
                    template = _.template(template);
                }
                select2Opts.formatSelection = _.bind(function(item) {
                    var result;
                    if (item !== null) {
                        result = this.formatChoice(item.id, template);
                    }
                    return result;
                }, this);
            }
            return select2Opts;
        },

        reset: function(entity, data) {
            this.setValue('');
            this.updateData(entity, data);
        },

        updateData: function(entity, data) {
            data = data || {};
            this.entity = entity;
            this.data = data;

            this.util.init(entity, data);
            this.$el.inputWidget('refresh');
        },

        getValue: function() {
            return this.$el.inputWidget('val');
        },

        setValue: function(value) {
            this.$el.inputWidget('val', value, true);
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
            var applicableConditions = this.util.getApplicableConditions(fieldId);
            if (_.isFunction(this.callbacks.applicableConditionsCallback)) {
                applicableConditions = this.callbacks.applicableConditionsCallback(applicableConditions, fieldId);
            }
            return applicableConditions;
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
            var entityData = this.data;
            var util = this.util;
            if ($.isEmptyObject(entityData)) {
                return results;
            }

            try {
                chain = this.util.pathToEntityChain(path, true);
                entityName = chain[chain.length - 1].entity.name;
            } catch (e) {
                return results;
            }

            entityData = entityData[entityName];
            entityFields = this.callbacks.dataFilter.call(this, entityName, entityData.fields);

            if (!_.isEmpty(this.exclude)) {
                entityFields = Util.filterFields(entityFields, this.exclude);
            }

            if (!_.isEmpty(this.include)) {
                entityFields = Util.filterFields(entityFields, this.include, true);
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

    return FieldChoiceView;
});
