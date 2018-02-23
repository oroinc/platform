define(function(require) {
    'use strict';

    var FieldChoiceView;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var Select2View = require('oroform/js/app/views/select2-view');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');

    FieldChoiceView = Select2View.extend({
        defaultOptions: {
            entity: null,
            dataFilter: function(entityName, entityFields) {
                return entityFields;
            },
            select2: {
                pageableResults: true,
                dropdownAutoWidth: true,
                allowClear: false
            },
            /*
             * When is TRUE, user can see relation in fields section and select it as value
             */
            allowSelectRelation: false
        },

        callbacks: {
            select2ResultsCallback: null,
            applicableConditionsCallback: null,
            dataFilter: null
        },

        /** @property {string} */
        filterPreset: void 0,

        /*
         * Array of rule objects or strings that will be used for entries filtering
         * examples:
         *      ['relation_type'] - will exclude all entries that has 'relation_type' key (means relational fields)
         *      [{name: 'id'}]    - will exclude all entries that has property "name" equals to "id"
         */
        exclude: null,
        /*
         * Format same as exclude option
         */
        include: null,

        /**
         * @inheritDoc
         */
        constructor: function FieldChoiceView() {
            FieldChoiceView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            options = $.extend(true, {}, this.defaultOptions, options);
            var optionNames = _.without(_.keys(this.defaultOptions), 'select2');
            optionNames.push('filterPreset', 'exclude', 'include');
            _.extend(this, _.pick(options, optionNames));
            this.callbacks = _.pick(options, _.keys(this.callbacks));
            this.select2Config = this._prepareSelect2Options(options);
            FieldChoiceView.__super__.initialize.call(this, options);
        },

        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.dataProvider;
            delete this.callbacks;
            delete this.exclude;
            delete this.include;
            delete this.dataFilter;
            FieldChoiceView.__super__.dispose.call(this);
        },

        onChange: function(e) {
            var selectedItem = e.added || this.getData();
            this.trigger('change', selectedItem);
        },

        render: function() {
            this._deferredRender();
            var providerOptions = {
                rootEntity: this.entity
            };
            _.each(['filterPreset', 'exclude', 'include'], function(key) {
                if (this[key]) {
                    providerOptions[key] = this[key];
                }
            }, this);
            if (this.callbacks.dataFilter) {
                providerOptions.fieldsFilterer = this.callbacks.dataFilter;
            }
            EntityStructureDataProvider.createDataProvider(providerOptions, this).then(function(provider) {
                if (this.disposed) {
                    this._rejectDeferredRender();
                    return;
                }
                if (this.entity !== provider.rootEntityClassName) {
                    provider.setRootEntityClassName(this.entity);
                }
                this.dataProvider = provider;
                FieldChoiceView.__super__.render.call(this);
                this._resolveDeferredRender();
            }.bind(this));
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
            _.extend(select2Opts, {
                initSelection: function(element, callback) {
                    var instance = element.data('select2');
                    var opts = instance.opts;
                    var id = element.val();
                    var match = null;
                    var chain;
                    try {
                        chain = this.dataProvider.pathToEntityChainExcludeTrailingField(id);
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
                    var instance = this.$el.data('select2');
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
                    var chain = [];
                    if (this.entity) {
                        chain = this.dataProvider.pathToEntityChainExcludeTrailingFieldSafely(pagePath);
                        _.each(chain, function(item) {
                            item.pagePath = item.basePath;
                        });
                    }
                    return chain;
                }.bind(this)
            });
            return select2Opts;
        },

        setEntity: function(entity) {
            this.entity = entity;

            if (this.dataProvider) {
                this.dataProvider.setRootEntityClassName(entity);
            }

            this.refresh();
        },

        formatChoice: function(value, template) {
            var data;
            if (value) {
                try {
                    data = this.dataProvider.pathToEntityChainSafely(value);
                } catch (e) {}
            }
            return data ? template(data) : value;
        },

        splitFieldId: function(fieldId) {
            return this.dataProvider.pathToEntityChainSafely(fieldId);
        },

        getApplicableConditions: function(fieldId) {
            var applicableConditions = this.dataProvider.getFieldSignatureSafely(fieldId);
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
            var entityFields;

            try {
                chain = this.dataProvider.pathToEntityChainExcludeTrailingField(path);
                entityFields = _.result(_.last(chain).entity, 'fields');
            } catch (e) {
                return results;
            }

            _.each(entityFields, function(field) {
                var chainItem = {field: field};
                var item = {
                    id: this.dataProvider.entityChainToPathSafely(chain.concat(chainItem)),
                    text: field.label
                };
                if (field.relationType) {
                    if (this.allowSelectRelation) {
                        fields.push(_.clone(item));
                    }
                    chainItem.entity = {className: field.relatedEntityName};
                    item.pagePath = this.dataProvider.entityChainToPathSafely(chain.concat(chainItem));
                    delete item.id;
                    relations.push(item);
                } else {
                    fields.push(item);
                }
            }, this);

            if (!_.isEmpty(fields)) {
                results.push({
                    text: __('oro.entity.field_choice.fields'),
                    children: _.sortBy(fields, 'text')
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
