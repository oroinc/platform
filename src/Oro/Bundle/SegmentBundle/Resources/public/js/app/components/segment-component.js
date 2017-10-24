define(function(require) {
    'use strict';

    var SegmentComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var FieldsCollection = require('../models/fields-collection');
    var __ = require('orotranslation/js/translator');
    var LoadingMask = require('oroui/js/app/views/loading-mask-view');
    var GroupingModel = require('oroquerydesigner/js/items-manager/grouping-model');
    var ColumnModel = require('oroquerydesigner/js/items-manager/column-model');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var EntityFieldsUtil = require('oroentity/js/entity-fields-util');
    var ColumnFormView = require('oroquerydesigner/js/app/views/column-form-view');

    require('oroentity/js/fields-loader');
    require('oroui/js/items-manager/editor');
    require('oroui/js/items-manager/table');

    SegmentComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            conditionBuilderComponent: 'condition-builder',
            columnFieldChoiceComponent: 'column-field-choice',
            groupingFieldChoiceComponent: 'grouping-field-choice',
            dateGroupingFieldChoiceComponent: 'date-grouping-field-choice'
        },

        defaults: {
            entityChoice: '',
            valueSource: '',
            fieldsLoader: {
                loadingMaskParent: '',
                router: null,
                routingParams: {},
                fieldsData: [],
                confirmMessage: '',
                loadEvent: 'fieldsLoaded'
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
            metadata: {},
            initEntityChangeEvents: true
        },

        columnFormView: null,

        initialize: function(options) {
            require(options.extensions || [], _.bind(function() {
                var extensions = arguments;
                _.each(extensions, function(extension) {
                    extension.load(this);
                }, this);

                this.processOptions(options);
                this.initStorage();

                this.initEntityFieldsUtil();
                this.$fieldsLoader = this.initFieldsLoader();
                this.initGrouping();
                this.initDateGrouping();
                this.initColumn();
                this.configureFilters();
                if (this.options.initEntityChangeEvents) {
                    this.initEntityChangeEvents();
                }

                SegmentComponent.__super__.initialize.call(this, options);

                this.form = this.$storage.parents('form');
                this.form.submit(_.bind(this.onBeforeSubmit, this));
            }, this));
        },

        _getInitFieldsData: function() {
            var fieldsLoaderOpts = this.options.fieldsLoader;
            if (fieldsLoaderOpts) {
                if (_.isString(fieldsLoaderOpts.fieldsData) && !_.isEmpty(fieldsLoaderOpts.fieldsData)) {
                    return JSON.parse(fieldsLoaderOpts.fieldsData);
                } else {
                    return fieldsLoaderOpts.fieldsData;
                }
            }
        },

        initStorage: function() {
            this.$storage = $(this.options.valueSource);
            this.$storage.on('change.' + this.cid, function() {
                this.trigger('updateData', this.load());
            }.bind(this));
        },

        initEntityChangeEvents: function() {
            var confirm = new DeleteConfirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes'),
                content: __(this.options.fieldsLoader.confirmMessage)
            });

            var self = this;
            this.$entityChoice.on('change', function(e, extraArgs) {
                _.extend(e, extraArgs);

                var data = self.load() || [];
                var requiresConfirm = _.some(data, function(value) {
                    return !_.isEmpty(value);
                });

                var ok = _.partial(_.bind(self._onEntityChangeConfirm, self), e, _.pick(e, 'val', 'removed'));

                var cancel = function() {
                    var oldVal = (e.removed && e.removed.id) || null;
                    self.$entityChoice.val(oldVal).change();
                };

                if (requiresConfirm) {
                    confirm.on('ok', ok);
                    confirm.on('cancel', cancel);
                    confirm.once('hidden', function() {
                        confirm.off('ok');
                        confirm.off('cancel');
                    });
                    confirm.open();
                } else {
                    ok();
                }
            });

            this.once('dispose:before', function() {
                confirm.dispose();
            });

            this.trigger(
                this.options.fieldsLoader.loadEvent,
                this.$fieldsLoader.val(),
                this.$fieldsLoader.fieldsLoader('getFieldsData'));
        },

        _onEntityChangeConfirm: function(e, additionalOptions) {
            this.$fieldsLoader.val(e.val).trigger('change', additionalOptions);
        },

        onBeforeSubmit: function(e) {
            var issues = [];

            // please note that event name, looks like method call
            // 'cause listeners will populate issues array by components
            this.trigger('validate-data', issues);

            if (!issues.length) {
                // Normal exit, form submitted
                this.trigger('before-submit');
                return;
            }

            issues = _.map(_.groupBy(issues, 'type'), function(items, type) {
                var components = _.map(items, function(item) {
                    return item.component;
                });
                components = {components: '<b>' + components.join('</b>, <b>') + '</b>'};
                return __('oro.segment.confirm.data_issue.' + type.toLowerCase(), components);
            });

            if (issues.length > 1) {
                issues = '<ul><li>' + issues.join('</li><li>') + '</li></ul>';
            } else {
                issues = issues[0];
            }

            var modal = new DeleteConfirmation({
                title: __('oro.segment.confirm.dialog.title'),
                content: __('oro.segment.confirm.dialog.message', {data_issue: issues}),
                okCloses: true,
                okText: __('OK')
            });

            modal.open(_.bind(function() {
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
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.trigger('dispose:before');
            delete this.options;
            this.$storage.off('.' + this.cid);
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
        formatChoice: function(value, template) {
            var data;
            if (value) {
                data = this.entityFieldsUtil.pathToEntityChain(value);
            }
            return data ? template(data) : value;
        },

        /**
         * Loads data from the input
         *
         * @param {string=} key name of data branch
         */
        load: function(key) {
            var data = {};
            var json = this.$storage.val();
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
        save: function(value, key) {
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
        processOptions: function(options) {
            this.options = {};
            $.extend(true, this.options, this.defaults, options);

            // common extra options for all choice inputs
            this.options.fieldChoiceOptions = {
                select2: {
                    formatSelectionTemplate: $(this.options.select2FieldChoiceTemplate).text()
                }
            };
        },

        /**
         * Initializes EntityFieldsUtil
         */
        initEntityFieldsUtil: function() {
            this.entityFieldsUtil = new EntityFieldsUtil();
            this.on('fieldsLoaded', this.entityFieldsUtil.init, this.entityFieldsUtil);
            this.once('dispose:before', function() {
                delete this.entityFieldsUtil;
            });
        },

        /**
         * Initializes FieldsLoader on entityChoice element
         */
        initFieldsLoader: function(loaderOptions) {
            var self = this;
            var options = loaderOptions || this.options.fieldsLoader;
            var loadingMask = new LoadingMask({
                container: $(options.loadingMaskParent)
            });

            this.$entityChoice = $(options.entityChoice);

            var entityChoiceCloneId = this.$entityChoice.data('ftid') + options.router;
            var $entityChoiceClone = $('<input>').attr({
                'id': entityChoiceCloneId,
                'class': 'hide',
                'data-ftid': entityChoiceCloneId
            });
            this.$entityChoice.after($entityChoiceClone.prop('outerHTML'));
            var $entityChoice = $('#' + entityChoiceCloneId);
            $entityChoice.val(this.$entityChoice.val());
            $entityChoice.data('relatedChoice', this.$entityChoice);

            $entityChoice
                .fieldsLoader({
                    router: options.router,
                    routingParams: options.routingParams
                })
                .on('fieldsloaderstart', _.bind(loadingMask.show, loadingMask))
                .on('fieldsloadercomplete', _.bind(loadingMask.hide, loadingMask))
                .on('fieldsloaderupdate', function(e, data) {
                    if (!loaderOptions) {
                        self.$entityChoice.trigger('fieldsloaderupdate', data);
                    }
                    self.trigger(options.loadEvent, $(e.target).val(), data);
                })
                .on('fieldsloadercomplete', function() {
                    var data = {};
                    self.trigger('resetData', data);
                    self.save(data);
                });

            $entityChoice.fieldsLoader('setFieldsData', this._getInitFieldsData());

            this.once('dispose:before', function() {
                loadingMask.dispose();
                delete this.$entityChoice;
            }, this);

            return $entityChoice;
        },

        /**
         * Initializes Fields Grouping component
         */
        initGrouping: function() {
            var self = this;
            var options = this.options.grouping;
            var $table = $(options.itemContainer);
            var $editor = $(options.form);

            if (_.isEmpty($table) || _.isEmpty($editor) || !this.groupingFieldChoiceComponent) {
                // there's no grouping
                return;
            }

            var groupingFieldChoiceView = this.groupingFieldChoiceComponent.view;

            if (this.options.metadata.grouping) {
                groupingFieldChoiceView.setFieldChoiceOptions(this.options.metadata.grouping);
            }
            groupingFieldChoiceView.updateData(this.$entityChoice.val(),
                EntityFieldsUtil.convertData(this._getInitFieldsData()));

            this.listenTo(this.$entityChoice, 'fieldsloaderupdate', function(e, data) {
                this.groupingFieldChoiceComponent.view.updateData($(e.target).val(), data);
            });

            // prepare collection for Items Manager
            var collection = new FieldsCollection(this.load('grouping_columns'), {
                model: GroupingModel,
                entityFieldsUtil: this.entityFieldsUtil
            });
            this.listenTo(collection, 'add remove sort change', function() {
                this.save(collection.toJSON(), 'grouping_columns');
            });

            // setup confirmation dialog for delete item
            var confirm = new DeleteConfirmation({content: ''});
            confirm.on('ok', function() {
                collection.remove(this.model);
            });
            confirm.on('hidden', function() {
                delete this.model;
            });

            // setup Items Manager's editor
            $editor.itemsManagerEditor($.extend(options.editor, {
                collection: collection
            }));

            this.on('validate-data', function(issues) {
                if ($editor.itemsManagerEditor('hasChanges')) {
                    issues.push({
                        component: __('oro.segment.grouping_editor'),
                        type: SegmentComponent.UNSAVED_CHANGES_ISSUE
                    });
                }
                if (!collection.isValid()) {
                    issues.push({
                        component: __('oro.segment.grouping_editor'),
                        type: SegmentComponent.INVALID_DATA_ISSUE
                    });
                }
            });

            this.on('before-submit', function() {
                collection.removeInvalidModels();
                $editor.itemsManagerEditor('reset');
            });

            // setup Items Manager's table
            var template = _.template(this.options.fieldChoiceOptions.select2.formatSelectionTemplate);
            $table.itemsManagerTable({
                collection: collection,
                itemTemplate: $(options.itemTemplate).html(),
                itemRender: function(tmpl, data) {
                    try {
                        data.name = self.formatChoice(data.name, template);
                    } catch (e) {
                        data.name = __('oro.querydesigner.field_not_found');
                        data.deleted = true;
                    }
                    return tmpl(data);
                },
                deleteHandler: function(model, data) {
                    confirm.setContent(data.message);
                    confirm.model = model;
                    confirm.open();
                }
            });

            this.on('resetData', function(data) {
                data.grouping_columns = [];
                $table.itemsManagerTable('reset');
                $editor.itemsManagerEditor('reset');
            });

            this.once('dispose:before', function() {
                confirm.dispose();
                collection.dispose();
                $editor.itemsManagerEditor('destroy');
                $table.itemsManagerTable('destroy');
            }, this);
        },

        initDateGrouping: function() {
            if (!this.dateGroupingFieldChoiceComponent) {
                // there's no date grouping
                return;
            }

            var fieldsData = EntityFieldsUtil.convertData(this._getInitFieldsData());

            this.dateGroupingFieldChoiceComponent.view.updateData(this.$entityChoice.val(), fieldsData);

            this.listenTo(this.$entityChoice, 'fieldsloaderupdate', function(e, data) {
                this.groupingFieldChoiceComponent.view.updateData($(e.target).val(), data);
            });
        },

        /**
         * Initializes Columns component
         */
        initColumn: function() {
            var self = this;
            var options = this.options.column;
            var metadata = this.options.metadata;
            var $table = $(options.itemContainer);
            var $form = $(options.form);

            if (_.isEmpty($form) || !this.columnFieldChoiceComponent) {
                // there's no columns
                return;
            }

            // setup FieldChoice of Items Manager Editor
            var fieldChoiceView = this.columnFieldChoiceComponent.view;
            fieldChoiceView.updateData(
                this.$entityChoice.val(), EntityFieldsUtil.convertData(this._getInitFieldsData())
            );

            this.columnFormView = new ColumnFormView({
                el: $form,
                autoRender: true,
                fieldChoiceView: fieldChoiceView,
                functionChoiceOptions: {
                    converters: metadata.converters,
                    aggregates: metadata.aggregates
                }
            });

            var $editor = this.columnFormView.$el;

            this.listenTo(this.$entityChoice, 'fieldsloaderupdate', function(e, data) {
                this.columnFieldChoiceComponent.view.reset($(e.target).val(), data);
            });

            // prepare collection for Items Manager
            var collection = new FieldsCollection(this.load('columns'), {
                model: ColumnModel,
                entityFieldsUtil: this.entityFieldsUtil
            });
            this.listenTo(collection, 'add remove sort change', function() {
                this.save(collection.toJSON(), 'columns');
            });

            // setup confirmation dialog for delete item
            var confirm = new DeleteConfirmation({content: ''});
            confirm.on('ok', function() {
                collection.remove(this.model);
            });
            confirm.on('hidden', function() {
                delete this.model;
            });

            $editor.itemsManagerEditor($.extend(options.editor, {
                collection: collection,
                setter: function($el, name, value) {
                    if (name === 'func') {
                        value = value.name;
                    }
                    return value;
                },
                getter: function($el, name, value) {
                    if (name === 'func') {
                        value = value && {
                            name: value,
                            group_type: $el.find(':selected').data('group_type'),
                            group_name: $el.find(':selected').data('group_name')
                        };

                        var returnType = $el.find(':selected').data('return_type');
                        if (value && returnType) {
                            value.return_type = returnType;
                        }
                    }
                    return value;
                }
            }));

            var sortingLabels = {};
            $editor.find('select[name*=sorting]').find('option:not([value=""])').each(function() {
                sortingLabels[this.value] = $(this).text();
            });

            this.on('validate-data', function(issues) {
                if ($editor.itemsManagerEditor('hasChanges')) {
                    issues.push({
                        component: __('oro.segment.report_column_editor'),
                        type: SegmentComponent.UNSAVED_CHANGES_ISSUE
                    });
                }
                if (!collection.isValid()) {
                    issues.push({
                        component: __('oro.segment.report_column_editor'),
                        type: SegmentComponent.INVALID_DATA_ISSUE
                    });
                }
            });

            this.on('before-submit', function() {
                collection.removeInvalidModels();
                $editor.itemsManagerEditor('reset');
            });

            var template = _.template(this.options.fieldChoiceOptions.select2.formatSelectionTemplate);
            $table.itemsManagerTable({
                collection: collection,
                itemTemplate: $(options.itemTemplate).html(),
                itemRender: function(tmpl, data) {
                    var item;
                    var itemFunc;
                    var func = data.func;

                    try {
                        data.name = self.formatChoice(data.name, template);
                    } catch (e) {
                        data.name = __('oro.querydesigner.field_not_found');
                        data.deleted = true;
                    }

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
                deleteHandler: function(model, data) {
                    confirm.setContent(data.message);
                    confirm.model = model;
                    confirm.open();
                }
            });

            this.on('resetData', function(data) {
                data.columns = [];
                $table.itemsManagerTable('reset');
                $editor.itemsManagerEditor('reset');
            });

            this.once('dispose:before', function() {
                confirm.dispose();
                collection.dispose();
                $editor.itemsManagerEditor('destroy');
                $table.itemsManagerTable('destroy');
            }, this);
        },

        configureFilters: function() {
            if (!this.conditionBuilderComponent) {
                // there's no condition builder
                return;
            }

            var setRootEntity = function(entity) {
                // defines the selected entity in the options of all criteria conditions
                this.conditionBuilderComponent.view.updateCriteriaOptions({
                    rootEntity: entity
                });
            }.bind(this);
            setRootEntity(this.entityFieldsUtil.entity);
            this.on('fieldsLoaded', setRootEntity);

            this.conditionBuilderComponent.view.setValue(this.load('filters'));
            this.listenTo(this.conditionBuilderComponent.view, 'change', function(value) {
                this.save(value, 'filters');
            });

            this.on('resetData', function(data) {
                data.filters = [];
                this.conditionBuilderComponent.view.setValue(data.filters);
            }, this);
            this.on('updateData', function(data) {
                this.conditionBuilderComponent.view.setValue(data.filters);
            }, this);
        }
    }, {
        INVALID_DATA_ISSUE: 'INVALID_DATA',
        UNSAVED_CHANGES_ISSUE: 'UNSAVED_CHANGES'
    });

    return SegmentComponent;
});
