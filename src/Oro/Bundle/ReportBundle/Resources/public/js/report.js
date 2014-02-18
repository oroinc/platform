/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';
    var defaults,
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
            form: '',
            itemContainer: '',
            itemTemplate: ''
        },
        column: {
            form: '',
            itemContainer: '',
            itemTemplate: ''
        },
        select2FormatSelectionTemplate: '',
        entities: [],
        metadata: {}
    };

    function load(key) {

    }

    function save(key, data) {

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
        var $editor, $fieldChoice, collection;

        $editor = $(options.form);
        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(fieldChoiceOptions, metadata.grouping);

        GroupingModel.prototype.util = $fieldChoice.data('oroentity-fieldChoice').entityFieldUtil;
        GroupingModel.prototype.nameTemplate = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        collection = new (Backbone.Collection)(load('grouping_columns'), {model: GroupingModel});
        collection.on('add remove sort', function () {
            save('grouping_columns', collection.toJSON());
        });

        $editor.itemsManagerEditor({
            namePattern:  /^oro_report_form\[grouping\]\[([\w\W]*)\]$/,
            collection: collection
        });
        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplateSelector: options.itemTemplate
        });
    }

    function initColumn(options, metadata, fieldChoiceOptions) {
        var $editor, $fieldChoice, collection;

        $editor = $(options.form);
        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(fieldChoiceOptions);

        $editor.find('[data-purpose=function-selector]').functionChoice({
            converters: metadata.converters,
            aggregates: metadata.aggregates
        });

        ColumnModel.prototype.util = $fieldChoice.data('oroentity-fieldChoice').entityFieldUtil;
        ColumnModel.prototype.nameTemplate = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        collection = new (Backbone.Collection)(load('columns'), {model: ColumnModel});
        collection.on('add remove sort', function () {
            save('columns', collection.toJSON());
        });

        $editor.itemsManagerEditor({
            namePattern:  /^oro_report_form\[column\]\[([\w\W]*)\]$/,
            collection: collection
        });

        $(options.itemContainer).itemsManagerTable({
            collection: collection,
            itemTemplateSelector: options.itemTemplate
        });
    }

    function configureFilters(options, metadata, fieldChoiceOptions) {
        // mixin extra options to condition-builder's field choice
        var $fieldCondition = $(options.criteriaList).find('[data-criteria=condition-item]');
        $.extend(true, $fieldCondition.data('options'), {
            fieldChoice: fieldChoiceOptions,
            filters: metadata.filters
        });
    }

    return function (options) {
        var fieldChoiceOptions;
        options = $.extend(true, {}, defaults, options);

        // common extra options for all field-choice inputs
        fieldChoiceOptions = getFieldChoiceOptions(options);

        initGrouping(options.grouping, options.metadata, fieldChoiceOptions);
        initColumn(options.column, options.metadata, fieldChoiceOptions);
        configureFilters(options.filters, options.metadata, fieldChoiceOptions);
    };
});
