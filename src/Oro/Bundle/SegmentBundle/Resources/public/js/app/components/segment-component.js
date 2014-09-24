/*global define*/
/*jslint nomen: true*/
define(function (require) {
    'use strict';
    var SegmentComponent,
        $ = require('jquery'),
        _ = require('underscore'),
        BaseComponent = require('oroui/js/app/components/base/component'),
        BaseCollection = require('oroui/js/app/models/base/collection'),
        __ = require('orotranslation/js/translator'),
        LoadingMask = require('oroui/js/loading-mask'),
        GroupingModel = require('oroquerydesigner/js/items-manager/grouping-model'),
        ColumnModel = require('oroquerydesigner/js/items-manager/column-model'),
        DeleteConfirmation = require('oroui/js/delete-confirmation'),
        EntityFieldsUtil = require('oroentity/js/entity-fields-util');
    require('oroentity/js/field-choice');
    require('oroentity/js/fields-loader');
    require('orosegment/js/segment-choice');
    require('oroui/js/items-manager/editor');
    require('oroui/js/items-manager/table');
    require('oroquerydesigner/js/condition-builder');

    SegmentComponent = BaseComponent.extend({
        defaults: {
            entityChoice: '',
            valueSource: '',
            fieldsLoader: {
                loadingMaskParent: '',
                router: null,
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
        },

        initialize: function (options) {
            this.processOptions(options);
            this.$storage = $(this.options.valueSource);

            this.initEntityFieldsUtil();
            this.initFieldsLoader();
            this.initGrouping();
            this.initColumn();
            this.configureFilters();

            if (this.$entityChoice) {
                this.trigger('fieldsLoaded',
                    this.$entityChoice.val(), this.$entityChoice.fieldsLoader('getFieldsData'));
            }

            SegmentComponent.__super__.initialize.call(this, options);

            this.form = this.$storage.parents('form');
            this.form.submit(_.bind(this.onBeforeSubmit, this));
        },

        onBeforeSubmit: function (e) {
            var unsavedComponents = [], modal;

            // please note that event name, looks like method call
            // 'cause listeners will populate unsavedComponents array
            this.trigger('find-unsaved-components', unsavedComponents);

            if (!unsavedComponents.length) {
                // Normal exit, form submitted
                this.trigger('before-submit');
                return;
            }

            modal = new DeleteConfirmation({
                title: __('oro.segment.confirm.unsaved_changes.title'),
                content: __('oro.segment.confirm.unsaved_changes.message', {components: unsavedComponents.join(', ')}),
                okCloses: true,
                okText: __('Ok')
            });

            modal.open(_.bind(function () {
                // let sub-components do cleanup before submit
                this.trigger('before-submit');
                this.form.trigger('submit');
            }, this));

            // prevent form submitting
            e.preventDefault();
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed) {
                return;
            }

            this.trigger('dispose:before');
            delete this.options;
            delete this.$storage;
            SegmentComponent.__super__.dispose.call(this);
        },

        /**
         * Renders HTML entity's field
         *
         * @param {string} value
         * @param {Function} template
         * @returns {string}
         */
        formatChoice: function (value, template) {
            var data;
            if (value) {
                try {
                    data = this.entityFieldsUtil.pathToEntityChain(value);
                } catch (e) {}
            }
            return data ? template(data) : value;
        },

        /**
         * Loads data from the input
         *
         * @param {string=} key name of data branch
         */
        load: function (key) {
            var data = {},
                json = this.$storage.val();
            if (json) {
                try {
                    data = JSON.parse(json);
                } catch (e) {
                    return undefined;
                }
            }
            return key ? data[key] : data;
        },

        /**
         * Saves data to the input
         *
         * @param {Object} value data from certain control
         * @param {string=} key name of data branch
         */
        save: function (value, key) {
            var data = this.load();
            if (key) {
                data[key] = value;
            } else {
                data = key;
            }
            this.$storage.val(JSON.stringify(data));
        },

        /**
         * Combines options
         *
         * @param {Object} options
         */
        processOptions: function (options) {
            this.options = {};
            $.extend(true, this.options, this.defaults, options);

            // common extra options for all choice inputs
            this.options.fieldChoiceOptions = {
                select2: {
                    formatSelectionTemplate: $(this.options.select2FieldChoiceTemplate).text()
                }
            };

            // options column's filed choice input
            if (!this.options.columnFieldChoiceOptions) {
                this.options.columnFieldChoiceOptions = {};
            }
            _.defaults(this.options.columnFieldChoiceOptions, this.options.fieldChoiceOptions);

            // options for segment choice
            this.options.segmentChoiceOptions = {};
            $.extend(true, this.options.segmentChoiceOptions, this.options.fieldChoiceOptions, {
                select2: {
                    formatSelectionTemplate: $(this.options.select2SegmentChoiceTemplate).text()
                }
            });
        },

        /**
         * Initializes EntityFieldsUtil
         */
        initEntityFieldsUtil: function () {
            this.entityFieldsUtil = new EntityFieldsUtil();
            this.on('fieldsLoaded', this.entityFieldsUtil.init, this.entityFieldsUtil);
            this.once('dispose:before', function () {
                delete this.entityFieldsUtil;
            });
        },

        /**
         * Initializes FieldsLoader on entityChoice element
         */
        initFieldsLoader: function () {
            var self, options, confirm, loadingMask, $entityChoice;

            self = this;
            options = this.options.fieldsLoader;

            loadingMask = new LoadingMask();
            $(options.loadingMaskParent).append(loadingMask.render().$el);

            confirm = new DeleteConfirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes'),
                content: __(options.confirmMessage)
            });

            $entityChoice = this.$entityChoice = $(options.entityChoice);
            $entityChoice
                .fieldsLoader({
                    router: options.router,
                    routingParams: options.routingParams,
                    confirm: confirm,
                    requireConfirm: function () {
                        var data = self.load();
                        if (!data) {
                            return false;
                        }
                        return _.some(data, function (value) {
                            return !_.isEmpty(value);
                        });
                    }
                })
                .on('fieldsloaderstart', _.bind(loadingMask.show, loadingMask))
                .on('fieldsloadercomplete', _.bind(loadingMask.hide, loadingMask))
                .on('fieldsloaderupdate', function (e, data) {
                    self.trigger('fieldsLoaded', $(e.target).val(), data);
                })
                .on('fieldsloadercomplete', function () {
                    var data = {};
                    self.trigger('resetData', data);
                    self.save(data);
                });

            if (!_.isEmpty(options.fieldsData)) {
                $entityChoice.fieldsLoader('setFieldsData', JSON.parse(options.fieldsData));
            }

            this.once('dispose:before', function () {
                loadingMask.dispose();
                confirm.dispose();
                delete this.$entityChoice;
            }, this);
        },

        /**
         * Initializes Fields Grouping component
         */
        initGrouping: function () {
            var self, options, fieldChoiceOptions, confirm,
                $table, $editor, $fieldChoice, collection, template;

            self = this;
            options = this.options.grouping;

            $table = $(options.itemContainer);
            $editor = $(options.form);

            if (_.isEmpty($table) || _.isEmpty($editor)) {
                // there's no grouping
                return;
            }

            // setup FieldChoice of Items Manager Editor
            fieldChoiceOptions = _.extend({}, this.options.fieldChoiceOptions, this.options.metadata.grouping, {select2: {}});
            $fieldChoice = $editor.find('[data-purpose=column-selector]');
            $fieldChoice.fieldChoice(fieldChoiceOptions);
            this.on('fieldsLoaded', function (entity, data) {
                $fieldChoice.fieldChoice('updateData', entity, data);
            });

            // prepare collection for Items Manager
            collection = new BaseCollection(this.load('grouping_columns'), {model: GroupingModel});
            this.listenTo(collection, 'add remove sort change', function () {
                this.save(collection.toJSON(), 'grouping_columns');
            });

            // setup confirmation dialog for delete item
            confirm = new DeleteConfirmation({content: ''});
            confirm.on('ok', function () {
                collection.remove(this.model);
            });
            confirm.on('hidden', function () {
                delete this.model;
            });

            // setup Items Manager's editor
            $editor.itemsManagerEditor($.extend(options.editor, {
                collection: collection
            }));

            this.on('find-unsaved-components', function (unsavedComponents) {
                if ($editor.itemsManagerEditor('hasChanges')) {
                    unsavedComponents.push(__('oro.segment.grouping_editor'));
                }
            });

            this.on('before-submit', function () {
                $editor.itemsManagerEditor('reset');
            });

            // setup Items Manager's table
            template = _.template(this.options.fieldChoiceOptions.select2.formatSelectionTemplate);
            $table.itemsManagerTable({
                collection: collection,
                itemTemplate: $(options.itemTemplate).html(),
                itemRender: function (tmpl, data) {
                    data.name = self.formatChoice(data.name, template);
                    return tmpl(data);
                },
                deleteHandler: function (model, data) {
                    confirm.setContent(data.message);
                    confirm.model = model;
                    confirm.open();
                }
            });

            this.on('resetData', function (data) {
                data.grouping_columns = [];
                $table.itemsManagerTable('reset');
                $editor.itemsManagerEditor('reset');
            });

            this.once('dispose:before', function () {
                confirm.dispose();
                collection.dispose();
                $editor.itemsManagerEditor('destroy');
                $table.itemsManagerTable('destroy');
            }, this);
        },

        /**
         * Initializes Columns component
         */
        initColumn: function () {
            var self, options, metadata, fieldChoiceOptions, confirm,
                $table, $editor, $fieldChoice, collection, template, sortingLabels;

            self = this;
            options = this.options.column;
            metadata = this.options.metadata;

            $table = $(options.itemContainer);
            $editor = $(options.form);

            if (_.isEmpty($table) || _.isEmpty($editor)) {
                // there's no columns
                return;
            }

            // setup FieldChoice of Items Manager Editor
            fieldChoiceOptions = _.extend({}, this.options.columnFieldChoiceOptions, {select2: {}});
            $fieldChoice = $editor.find('[data-purpose=column-selector]');
            $fieldChoice.fieldChoice(fieldChoiceOptions);
            this.on('fieldsLoaded', function (entity, data) {
                $fieldChoice.fieldChoice('updateData', entity, data);
            });

            // prepare collection for Items Manager
            collection = new BaseCollection(this.load('columns'), {model: ColumnModel});
            this.listenTo(collection, 'add remove sort change', function () {
                this.save(collection.toJSON(), 'columns');
            });

            // setup confirmation dialog for delete item
            confirm = new DeleteConfirmation({content: ''});
            confirm.on('ok', function () {
                collection.remove(this.model);
            });
            confirm.on('hidden', function () {
                delete this.model;
            });

            // setup Items Manager's editor
            $editor.find('[data-purpose=function-selector]').functionChoice({
                converters: metadata.converters,
                aggregates: metadata.aggregates
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
                            group_type: $el.find(':selected').data('group_type'),
                            group_name: $el.find(':selected').data('group_name')
                        };
                    }
                    return value;
                }
            }));

            sortingLabels = {};
            $editor.find('select[name*=sorting]').find('option:not([value=""])').each(function () {
                sortingLabels[this.value] = $(this).text();
            });

            this.on('find-unsaved-components', function (unsavedComponents) {
                if ($editor.itemsManagerEditor('hasChanges')) {
                    unsavedComponents.push(__('oro.segment.report_column_editor'));
                }
            });

            this.on('before-submit', function () {
                $editor.itemsManagerEditor('reset');
            });

            template = _.template(this.options.columnFieldChoiceOptions.select2.formatSelectionTemplate);
            $table.itemsManagerTable({
                collection: collection,
                itemTemplate: $(options.itemTemplate).html(),
                itemRender: function (tmpl, data) {
                    var item, itemFunc,
                        func = data.func;

                    data.name = self.formatChoice(data.name, template);
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
                deleteHandler: function (model, data) {
                    confirm.setContent(data.message);
                    confirm.model = model;
                    confirm.open();
                }
            });

            this.on('resetData', function (data) {
                data.columns = [];
                $table.itemsManagerTable('reset');
                $editor.itemsManagerEditor('reset');
            });

            this.once('dispose:before', function () {
                confirm.dispose();
                collection.dispose();
                $editor.itemsManagerEditor('destroy');
                $table.itemsManagerTable('destroy');
            }, this);
        },

        configureFilters: function () {
            var self, options, metadata,
                $fieldCondition, $segmentCondition, $builder, $criteria;

            self = this;
            options = this.options.filters;
            metadata = this.options.metadata;

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
                    fieldChoice: this.options.fieldChoiceOptions,
                    filters: metadata.filters,
                    hierarchy: metadata.hierarchy
                });
            }

            $segmentCondition = $criteria.find('[data-criteria=condition-segment]');
            if (!_.isEmpty($segmentCondition)) {
                $.extend(true, $segmentCondition.data('options'), {
                    segmentChoice: this.options.segmentChoiceOptions,
                    filters: metadata.filters
                });
            }

            $builder.conditionBuilder({
                criteriaListSelector: options.criteriaList
            });
            $builder.conditionBuilder('setValue', this.load('filters'));
            $builder.on('changed', function () {
                self.save($builder.conditionBuilder('getValue'), 'filters');
            });

            this.on('resetData', function (data) {
                data.filters = [];
                $builder.conditionBuilder('setValue', data.filters);
            });

            this.once('dispose:before', function () {
                $builder.conditionBuilder('destroy');
            }, this);
        }
    });

    return SegmentComponent;
});
