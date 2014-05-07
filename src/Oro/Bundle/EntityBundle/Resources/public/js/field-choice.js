/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';

    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    require('jquery-ui');
    require('jquery.select2');

    function filterFields(fields, exclude) {
        fields = _.filter(fields, function (item) {
            var result;
            // otherwise - we filter by object keys or not filtering at all
            result = !_.some(exclude, function (rule) {
                var result;
                // exclude can be a property name
                if (_.isString(rule)) {
                    result = _.intersection(
                        [rule],
                        _.keys(item)
                    ).length > 0;
                } else {
                    // or exclude can be an object with data to compare
                    var cut = _.pick(item, _.keys(rule));
                    result  = _.isEqual(cut, rule);
                }

                return result;
            });
            return result;
        });
        return fields;
    }

    $.widget('oroentity.fieldChoice', {
        options: {
            entity: null,
            fields: {},
            select2: {
                pageableResults: true,
                dropdownAutoWidth: true
            },
            exclude: [],
            fieldsLoaderSelector: ''
        },

        _create: function () {
            this._on({
                change: function (e) {
                    if (e.added) {
                        this.element.trigger('changed', e.added.id);
                    }
                }
            });

            this._bindFieldsLoader();
        },

        _init: function () {
            var instance, select2Options,
                self = this;

            this._processSelect2Options();
            this._updateData(this.options.entity, this.options.fields);

            select2Options = $.extend({
                initSelection: function (element, callback) {
                    var id, chain, opts, match;
                    opts = instance.opts;
                    id = element.val();
                    match = null;
                    chain = self._pathToEntityChain(id, true);
                    instance.context = self._entityChainToPath(chain);
                    opts.query({
                        matcher: function (term, text, el) {
                            var is_match = id === opts.id(el);
                            if (is_match) {
                                match = el;
                            }
                            return is_match;
                        },
                        callback: !$.isFunction(callback) ? $.noop : function () {
                            callback(match);
                        }
                    });
                },
                id: function (result) {
                    return result.id !== undefined ? result.id : result.context;
                },
                data: function () {
                    var context, results;
                    context = (instance && instance.context) || '';
                    results = self._select2Data(context);
                    return {
                        more: false,
                        context: context,
                        results: results
                    };
                },
                formatBreadcrumbItem: function (item) {
                    var label;
                    label = item.field ? item.field.label : item.entity.label;
                    return label;
                },
                breadcrumbs: function (context) {
                    var chain = self._pathToEntityChain(context, true);
                    $.each(chain, function (i, item) {
                        item.context = item.path;
                    });
                    return chain;
                }
            }, this.options.select2);

            this.element.select2(select2Options);
            instance = this.element.data('select2');
        },

        _setOption: function (key, value) {
            if ($.isPlainObject(value)) {
                $.extend(this.options[key], value);
            } else {
                this._super(key, value);
            }
            return this;
        },

        _getCreateOptions: function () {
            return $.extend(true, {}, this.options);
        },

        _processSelect2Options: function () {
            var template,
                options = this.options.select2;

            if (options.formatSelectionTemplate) {
                template = _.template(options.formatSelectionTemplate);
                options.formatSelection = $.proxy(function (item) {
                    return this.formatChoice(item.id, template);
                }, this);
            }
        },

        _bindFieldsLoader: function () {
            var self = this, $fieldsLoader;
            if (!this.options.fieldsLoaderSelector) {
                return;
            }
            $fieldsLoader = $(this.options.fieldsLoaderSelector);
            $fieldsLoader.on('fieldsloaderupdate', function (e, fields) {
                self.setValue('');
                self._updateData($(e.target).val(), fields);
            });
            this._updateData($fieldsLoader.val(), $fieldsLoader.data('fields'));
        },

        _updateData: function (entity, data) {
            data = data || {};
            this.options.entity = entity;
            this.options.fields = data;

            this.element
                .data('entity', entity)
                .data('data', data);
        },

        getApplicableConditions: function (fieldId) {
            if (!fieldId) {
                return {};
            }

            var chain = this._pathToEntityChain(fieldId);
            var result = {
                parent_entity: null,
                entity: chain[chain.length - 1].field.entity.name,
                field: fieldId
            };
            if (chain.length > 2) {
                result.parent_entity = chain[chain.length - 2].field.entity.name;
            }
            _.extend(result, _.pick(chain[chain.length - 1].field, ['type', 'identifier']));

            return result;
        },

        setValue: function (value) {
            this.element.select2('val', value, true);
        },

        formatChoice: function (value, template) {
            return value ? template(this._pathToEntityChain(value)) : '';
        },

        splitFieldId: function (fieldId) {
            return this._pathToEntityChain(fieldId);
        },

        /**
         *
         * @param {string} path
         * @returns {Array}
         * @private
         */
        _select2Data: function (path) {
            var fields = [], relations = [], results = [],
                chain, entityName, entityFields,
                entityData = this.options.fields;
            if ($.isEmptyObject(entityData)) {
                return results;
            }

            chain = this._pathToEntityChain(path, true);
            path = this._entityChainToPath(chain);
            entityName = chain[chain.length - 1].entity.name;
            entityData = entityData[entityName];
            entityFields = entityData.fields;

            if (!_.isEmpty(this.options.exclude)) {
                entityFields = filterFields(entityFields, this.options.exclude);
            }

            $.each(entityFields, function () {
                var field = this, item;
                item = {
                    id: (path ? path + '::' : '') + field.name,
                    text: field.label
                };
                if (field.related_entity) {
                    item.context = item.id + '+' + field.related_entity.name;
                    item.related_entity = field.related_entity;
                    delete item.id;
                    relations.push(item);
                } else {
                    fields.push(item);
                }
            });

            if (!_.isEmpty(fields)) {
                results.push({
                    text: __("oro.entity.field_choice.fields"),
                    children: fields
                });
            }

            if (!_.isEmpty(relations)) {
                results.push({
                    text: __("oro.entity.field_choice.relations"),
                    children: relations
                });
            }

            return results;
        },

        /**
         *
         * @param {string} path
         * @param {boolean?} trim
         * @returns {Array.<Object>}
         * @private
         */
        _pathToEntityChain: function (path, trim) {
            var chain, data, self = this;
            data = this.options.fields;
            chain = [{
                entity: data[this.options.entity],
                path: ''
            }];

            $.each(path.split('::'), function (i, item) {
                var filedName, entityName;
                if (item) {
                    item = item.split('+');
                    filedName = item[0];
                    entityName = item[1];
                    if (!chain[i].entity) {
                        debugger;
                    }
                    item = {
                        field: chain[i].entity.fieldsIndex[filedName]
                    };
                    if (entityName) {
                        item.entity = data[entityName];
                    }
                    chain.push(item);
                    item.path = self._entityChainToPath(chain);
                }
            });

            // if last item in the chain is a field -- cut it off
            if (trim && chain[chain.length - 1].entity === undefined) {
                chain = chain.slice(0, -1);
            }

            return chain;
        },

        /**
         *
         * @param {Array.<Object>} chain
         * @param {number=} end
         * @returns {string}
         * @private
         */
        _entityChainToPath: function (chain, end) {
            var path;
            end = end || chain.length;

            chain = $.map(chain.slice(1, end), function (item) {
                var result = item.field.name;
                if (item.entity) {
                    result += '+' + item.entity.name;
                }
                return result;
            });

            path = chain.join('::');

            return path;
        }
    });

    return $;
});
