/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';
    var defaults, $storage,
        $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        ColumnModel = require('oroquerydesigner/js/items-manager/column-model'),
        DeleteConfirmation = require('oroui/js/delete-confirmation');
    require('oroentity/js/field-choice');
    require('orosegment/js/segment-choice');
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

    function deleteHandler(collection, model, data) {
        var confirm = new DeleteConfirmation({
            content: data.message
        });
        confirm.on('ok', function () {
            collection.remove(model);
        });
        confirm.open();
    }

    function initColumn(options, metadata, segmentChoiceOptions) {
        var $editor, $segmentChoice, collection, util, template, sortingLabels;

        $editor = $(options.form);
        $segmentChoice = $editor.find('[data-purpose=column-selector]');
        $segmentChoice.fieldChoice(_.extend({}, segmentChoiceOptions, {select2: {}}));

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
                    value = value && {
                        name: value,
                        group_type: $el.find(":selected").data('group_type'),
                        group_name: $el.find(":selected").data('group_name')
                    };
                }
                return value;
            }
        }));

        sortingLabels = {};
        $editor.find('select[name*=sorting]').find('option:not([value=""])').each(function () {
            sortingLabels[this.value] = $(this).text();
        });

        util = $segmentChoice.data('oroentity-fieldChoice').entityFieldUtil;
        template = _.template(segmentChoiceOptions.select2.formatSelectionTemplate);

        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                var name = util.splitFieldId(data.name),
                    func = data.func,
                    item, itemFunc;

                data.name = template(name);
                if (func && func.name) {
                    item = metadata[func.group_type][func.group_name];
                    if (item) {
                        itemFunc = _.findWhere(item.functions, {name: func.name});
                        if (itemFunc) {
                            data.func = itemFunc.label;
                        }
                    }
                } else {
                    data.func = '';
                }

                if (data.sorting && sortingLabels[data.sorting]) {
                    data.sorting = sortingLabels[data.sorting];
                }

                return tmpl(data);
            },
            deleteHandler: _.bind(deleteHandler, null, collection)
        });
    }

    function configureFilters(options, metadata, fieldChoiceOptions, segmentChoiceOptions) {
        var $fieldCondition, $segmentCondition, $builder;

        // mixin extra options to condition-builder's field choice
        $fieldCondition = $(options.criteriaList).find('[data-criteria=condition-item]');
        $.extend(true, $fieldCondition.data('options'), {
            fieldChoice: fieldChoiceOptions,
            filters: metadata.filters
        });

        $segmentCondition = $(options.criteriaList).find('[data-criteria=condition-segment]');
        $.extend(true, $segmentCondition.data('options'), {
            segmentChoice: segmentChoiceOptions,
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
        var fieldChoiceOptions, segmentChoiceOptions;
        options = $.extend(true, {}, defaults, options);

        $storage = $(options.valueSource);

        // common extra options for all segment-choice inputs
        fieldChoiceOptions = {
            select2: {
                formatSelectionTemplate: $(options.select2FieldChoiceTemplate.field).text()
            },
            util: {
                findEntity:  function (entityName) {
                    return _.findWhere(options.entities, {name: entityName});
                }
            }
        };
        segmentChoiceOptions = _.extend(_.clone(fieldChoiceOptions), {
            select2: {
                formatSelectionTemplate: $(options.select2FieldChoiceTemplate.segment).text()
            }
        });

        initColumn(options.column, options.metadata, fieldChoiceOptions);
        configureFilters(options.filters, options.metadata, fieldChoiceOptions, segmentChoiceOptions);

        $(options.entityChoice)
            .on('fieldsloadercomplete', function () {
                var data = {columns: [], filters: []};
                save(data);
                $(options.column.itemContainer).itemsManagerTable('reset');
                $(options.column.form).itemsManagerEditor('reset');
                $(options.filters.conditionBuilder).conditionBuilder('setValue', data.filters);
            });
    };
});
