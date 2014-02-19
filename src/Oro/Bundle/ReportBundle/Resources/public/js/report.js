/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';
    var defaults, $storage,
        $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        GroupingModel = require('oroquerydesigner/js/items-manager/grouping-model'),
        ColumnModel = require('oroquerydesigner/js/items-manager/column-model');
    require('oroentity/js/field-choice');
    require('oroui/js/items-manager/editor');
    require('oroui/js/items-manager/table');
    require('oroquerydesigner/js/condition-builder');

    defaults = {
        entityChoice: '',
        valueSource: '',
        filters: {
            criteriaList: '',
            conditionBuilder: ''
        },
        grouping: {
            editor: {},
            form: '',
            itemContainer: '',
            itemTemplate: ''
        },
        column: {
            editor: {},
            form: '',
            itemContainer: '',
            itemTemplate: ''
        },
        select2FieldChoiceTemplate: '',
        entities: [],
        metadata: {}
    };

    /**
     * Loads data from the input
     *
     * @param {string=} key name of data branch
     */
    function load(key) {
        var json = $storage.val(),
            data = (json && JSON.parse(json)) || {};
        return key ? data[key] : data;
    }

    /**
     * Saves data to the input
     *
     * @param {Object} value data from certain control
     * @param {string=} key name of data branch
     */
    function save(value, key) {
        var data = load();
        if (key) {
            data[key] = value;
        } else {
            data = key;
        }
        $storage.val(JSON.stringify(data));
    }

    function getFieldChoiceOptions(options) {
        return {
            select2: {
                formatSelectionTemplate: $(options.select2FieldChoiceTemplate).text()
            },
            util: {
                findEntity:  function (entityName) {
                    return _.findWhere(options.entities, {name: entityName});
                }
            }
        };
    }

    function initGrouping(options, metadata, fieldChoiceOptions) {
        var $editor, $fieldChoice, collection, util, template;

        $editor = $(options.form);
        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(fieldChoiceOptions, metadata.grouping);

        collection = new (Backbone.Collection)(load('grouping_columns'), {model: GroupingModel});
        collection.on('add remove sort', function () {
            save(collection.toJSON(), 'grouping_columns');
        });

        $editor.itemsManagerEditor($.extend(options.editor, {
            collection: collection
        }));

        util = $fieldChoice.data('oroentity-fieldChoice').entityFieldUtil;
        template = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                var name = util.splitFieldId(data.name);
                data.name = template(name);
                return tmpl(data);
            }
        });
    }

    function initColumn(options, metadata, fieldChoiceOptions) {
        var $editor, $fieldChoice, collection, util, template;

        $editor = $(options.form);
        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(fieldChoiceOptions);

        $editor.find('[data-purpose=function-selector]').functionChoice({
            converters: metadata.converters,
            aggregates: metadata.aggregates
        });

        collection = new (Backbone.Collection)(load('columns'), {model: ColumnModel});
        collection.on('add remove sort', function () {
            save(collection.toJSON(), 'columns');
        });

        $editor.itemsManagerEditor($.extend(options.editor, {
            collection: collection,
            setter: function ($el, name, value) {
                if (name === 'func') {
                    value = value.name;
                }
                return value;
            },
            getter: function ($el, name, value) {
                if (name === 'func') {
                    value = {
                        name: value,
                        group_type: $el.find(":selected").data('group_type'),
                        group_name: $el.find(":selected").data('group_name')
                    };
                }
                return value;
            }
        }));

        util = $fieldChoice.data('oroentity-fieldChoice').entityFieldUtil;
        template = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                var item, itemFunc,
                    func = data.func,
                    name = util.splitFieldId(data.name);

                data.name = template(name);
                if (func) {
                    item = metadata[func.group_type][func.group_name];
                    if (item) {
                        itemFunc = _.findWhere(item.functions, {name: func.name});
                        if (itemFunc) {
                            data.func = itemFunc.label;
                        }
                    }
                }

                return tmpl(data);
            }
        });
    }

    function configureFilters(options, metadata, fieldChoiceOptions) {
        var $fieldCondition, $builder;
        // mixin extra options to condition-builder's field choice
        $fieldCondition = $(options.criteriaList).find('[data-criteria=condition-item]');
        $.extend(true, $fieldCondition.data('options'), {
            fieldChoice: fieldChoiceOptions,
            filters: metadata.filters
        });
        $builder = $(options.conditionBuilder);
        $builder.conditionBuilder({
            criteriaListSelector: options.criteriaList
        });
        $builder.conditionBuilder('setValue', load('filters'));
        $builder.on('changed', function () {
            save($builder.conditionBuilder('getValue'), 'filters');
        });
    }

    return function (options) {
        var fieldChoiceOptions;
        options = $.extend(true, {}, defaults, options);

        $storage = $(options.valueSource);

        // common extra options for all field-choice inputs
        fieldChoiceOptions = getFieldChoiceOptions(options);

        initGrouping(options.grouping, options.metadata, fieldChoiceOptions);
        initColumn(options.column, options.metadata, fieldChoiceOptions);
        configureFilters(options.filters, options.metadata, fieldChoiceOptions);

        $(options.entityChoice)
            .on('fieldsloadercomplete', function () {
                var data = {columns: [], grouping_columns: [], filters: []};
                save(data);
                $(options.grouping.itemContainer).itemsManagerTable('reset');
                $(options.grouping.form).itemsManagerEditor('reset');
                $(options.column.itemContainer).itemsManagerTable('reset');
                $(options.column.form).itemsManagerEditor('reset');
                $(options.filters.conditionBuilder).conditionBuilder('setValue', data.filters);
            });

    };
});
