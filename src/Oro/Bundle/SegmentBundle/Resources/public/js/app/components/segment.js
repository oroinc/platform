/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';
    var defaults, $storage, messageBus,
        $ = require('jquery'),
        _ = require('underscore'),
        Backbone = require('backbone'),
        mediator = require('oroui/js/mediator'),
        __ = require('orotranslation/js/translator'),
        LoadingMask = require('oroui/js/loading-mask'),
        GroupingModel = require('oroquerydesigner/js/items-manager/grouping-model'),
        ColumnModel = require('oroquerydesigner/js/items-manager/column-model'),
        DeleteConfirmation = require('oroui/js/delete-confirmation');
    require('oroentity/js/field-choice');
    require('oroentity/js/fields-loader');
    require('orosegment/js/segment-choice');
    require('oroui/js/items-manager/editor');
    require('oroui/js/items-manager/table');
    require('oroquerydesigner/js/condition-builder');

    defaults = {
        entityChoice: '',
        valueSource: '',
        fieldsLoader: {
            loadingMaskParent: '',
            routingParams: {},
            fieldsData: [],
            confirmMessage: ''
        },
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
        select2SegmentChoiceTemplate: '',
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
            }
        };
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

    function initFieldsLoader(options) {
        var confirm, loadingMask, $entityChoice;

        loadingMask = new LoadingMask();
        $(options.loadingMaskParent).append(loadingMask.render().$el);

        confirm = new DeleteConfirmation({
            title: __('Change Entity Confirmation'),
            okText: __('Yes, I Agree'),
            content: __(options.confirmMessage)
        });

        $entityChoice = $(options.entityChoice);
        $entityChoice
            .fieldsLoader({
                router: 'oro_api_querydesigner_fields_entity',
                routingParams: options.routingParams,
                confirm: confirm,
                requireConfirm: function () {
                    var data = $storage.val();
                    if (!data) {
                        return false;
                    }
                    try {
                        data = JSON.parse(data);
                    } catch (e) {
                        return false;
                    }
                    return _.some(data, function (value) {
                        return !_.isEmpty(value);
                    });
                }
            })
            .on('fieldsloaderstart', $.proxy(loadingMask.show, loadingMask))
            .on('fieldsloadercomplete', $.proxy(loadingMask.hide, loadingMask))
            .on('fieldsloadercomplete', function () {
                var data = {};
                messageBus.trigger('resetData', data);
                save(data);
            });

        if (!_.isEmpty(options.fieldsData)) {
            $entityChoice.fieldsLoader('setFieldsData', JSON.parse(options.fieldsData));
        }
    }

    function initGrouping(options, metadata, fieldChoiceOptions) {
        var $itemContainer, $editor, $fieldChoice, collection, template;

        $itemContainer = $(options.itemContainer);
        $editor = $(options.form);

        if (_.isEmpty($itemContainer) || _.isEmpty($editor)) {
            // there's no grouping
            return;
        }

        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(_.extend({}, fieldChoiceOptions, metadata.grouping, {select2: {}}));

        collection = new (Backbone.Collection)(load('grouping_columns'), {model: GroupingModel});
        collection.on('add remove sort change', function () {
            save(collection.toJSON(), 'grouping_columns');
        });

        $editor.itemsManagerEditor($.extend(options.editor, {
            collection: collection
        }));

        template = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        $itemContainer.itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                data.name = $fieldChoice.fieldChoice('formatChoice', data.name, template);
                return tmpl(data);
            },
            deleteHandler: _.bind(deleteHandler, null, collection)
        });

        messageBus.on('resetData', function (data) {
            data.grouping_columns = [];
            $itemContainer.itemsManagerTable('reset');
            $editor.itemsManagerEditor('reset');
        });
    }

    function initColumn(options, metadata, fieldChoiceOptions) {
        var $itemContainer, $editor, $fieldChoice, collection, template, sortingLabels;

        $itemContainer = $(options.itemContainer);
        $editor = $(options.form);

        if (_.isEmpty($itemContainer) || _.isEmpty($editor)) {
            // there's no columns
            return;
        }

        $fieldChoice = $editor.find('[data-purpose=column-selector]');
        $fieldChoice.fieldChoice(_.extend({}, fieldChoiceOptions, {select2: {}}));

        $editor.find('[data-purpose=function-selector]').functionChoice({
            converters: metadata.converters,
            aggregates: metadata.aggregates
        });

        collection = new (Backbone.Collection)(load('columns'), {model: ColumnModel});
        collection.on('add remove sort change', function () {
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

        template = _.template(fieldChoiceOptions.select2.formatSelectionTemplate);

        $itemContainer.itemsManagerTable({
            collection: collection,
            itemTemplate: $(options.itemTemplate).html(),
            itemRender: function (tmpl, data) {
                var item, itemFunc,
                    func = data.func;

                data.name = $fieldChoice.fieldChoice('formatChoice', data.name, template);
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

        messageBus.on('resetData', function (data) {
            data.columns = [];
            $itemContainer.itemsManagerTable('reset');
            $editor.itemsManagerEditor('reset');
        });
    }

    function configureFilters(options, metadata, fieldChoiceOptions, segmentChoiceOptions) {
        var $fieldCondition, $segmentCondition, $builder, $criteria;

        $builder = $(options.conditionBuilder);
        $criteria = $(options.criteriaList);

        if (_.isEmpty($builder) || _.isEmpty($criteria)) {
            // there's no filter
            return;
        }

        // mixin extra options to condition-builder's field choice
        $fieldCondition = $criteria.find('[data-criteria=condition-item]');
        if (!_.isEmpty($fieldCondition)) {
            $.extend(true, $fieldCondition.data('options'), {
                fieldChoice: fieldChoiceOptions,
                filters: metadata.filters,
                hierarchy: metadata.hierarchy
            });
        }

        $segmentCondition = $criteria.find('[data-criteria=condition-segment]');
        if (!_.isEmpty($segmentCondition)) {
            $.extend(true, $segmentCondition.data('options'), {
                segmentChoice: segmentChoiceOptions,
                filters: metadata.filters
            });
        }

        $builder.conditionBuilder({
            criteriaListSelector: options.criteriaList
        });
        $builder.conditionBuilder('setValue', load('filters'));
        $builder.on('changed', function () {
            save($builder.conditionBuilder('getValue'), 'filters');
        });

        messageBus.on('resetData', function (data) {
            data.filters = [];
            $builder.conditionBuilder('setValue', data.filters);
        });
    }

    return function (options) {
        var fieldChoiceOptions, segmentChoiceOptions, gridFieldChoiceOptions;

        options = $.extend(true, {}, defaults, options);

        $storage = $(options.valueSource);
        messageBus = $.extend({}, Backbone.Events);
        mediator.once('page:request', function () {
            messageBus.off();
        });

        // common extra options for all choice inputs
        fieldChoiceOptions   = getFieldChoiceOptions(options);
        gridFieldChoiceOptions = _.defaults(options.gridFieldChoiceOptions || {}, fieldChoiceOptions);
        segmentChoiceOptions = _.extend(_.clone(fieldChoiceOptions), {
            select2: {
                formatSelectionTemplate: $(options.select2SegmentChoiceTemplate).text()
            }
        });

        initFieldsLoader(options.fieldsLoader);
        initGrouping(options.grouping, options.metadata, fieldChoiceOptions);
        initColumn(options.column, options.metadata, gridFieldChoiceOptions);
        configureFilters(options.filters, options.metadata, fieldChoiceOptions, segmentChoiceOptions);

        options._sourceElement.remove();
    };
});
