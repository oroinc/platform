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

    defaults = {
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
        select2FormatSelectionTemplate: '',
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
     * @param {string} key name of data branch
     * @param {Object} value data from certain control
     */
    function save(key, value) {
        var data = load();
        data[key] = value;
        $storage.val(JSON.stringify(data));
    }

    function getFieldChoiceOptions(options) {
        return {
            select2: {
                formatSelectionTemplate: $(options.select2FormatSelectionTemplate).text()
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
            save('grouping_columns', collection.toJSON());
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
            save('columns', collection.toJSON());
        });

        $editor.itemsManagerEditor($.extend(options.editor, {
            collection: collection,
            setPropertyEditor: function (name, value, $el, setValue) {
                if (name === 'func') {
                    setValue($el, value.name);
                    $el.data('group_type', value.group_type);
                    $el.data('group_name', value.group_name);
                } else {
                    setValue($el, value);
                }
            },
            readPropertyEditor: function (name, $el) {
                if (name === 'func') {
                    return {
                        name: $el.val(),
                        group_type: $el.find(":selected").data('group_type'),
                        group_name: $el.find(":selected").data('group_name')
                    };
                } else {
                    return $el.val();
                }
            }
        }));

        util = $fieldChoice.data('oroentity-fieldChoice').entityFieldUtil;
        template = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                var func = data.func,
                    name = util.splitFieldId(data.name);

                data.name = template(name);
                data.func = func && (func.group_type + ':' + func.group_name + ':' + func.name);

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
        $builder.conditionBuilder('setValue', load('filters'));
        $builder.on('changed', function () {
            save('filters', $builder.conditionBuilder('getValue'));
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
    };
});
