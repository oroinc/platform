define(function(require) {
    'use strict';

    var SegmentComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var tools = require('oroui/js/tools');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var EntityFieldsCollection = require('oroquerydesigner/js/app/models/entity-fields-collection');
    var GroupingModel = require('oroquerydesigner/js/app/models/grouping-model');
    var ColumnModel = require('oroquerydesigner/js/app/models/column-model');
    var DeleteConfirmation = require('oroui/js/delete-confirmation');
    var EntityStructureDataProvider = require('oroentity/js/app/services/entity-structure-data-provider');
    var ColumnFormView = require('oroquerydesigner/js/app/views/column-form-view');
    require('oroui/js/items-manager/editor');
    require('oroui/js/items-manager/table');

    SegmentComponent = BaseComponent.extend({
        relatedSiblingComponents: {
            conditionBuilderComponent: 'condition-builder',
            columnFieldChoiceComponent: 'column-field-choice',
            columnFunctionChoiceComponent: 'column-function-choice',
            groupingFieldChoiceComponent: 'grouping-field-choice',
            dateGroupingFieldChoiceComponent: 'date-grouping-field-choice'
        },

        defaults: {
            entityChoice: '',
            valueSource: '',
            dataProviderFilterPreset: 'querydesigner',
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
            metadata: {},
            initEntityChangeEvents: true
        },

        /**
         * Class name of currently selected entity
         * @type {string}
         */
        entityClassName: void 0,

        /**
         * @type {EntityStructureDataProvider}
         */
        dataProvider: null,

        /**
         * @type {ColumnFormView}
         */
        columnFormView: null,

        /**
         * @inheritDoc
         */
        constructor: function SegmentComponent() {
            SegmentComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var providerPromise = EntityStructureDataProvider.createDataProvider({}, this);
            var modulesPromise = !options.extensions ? [] : tools.loadModules(options.extensions).then(function() {
                // promise always has to return array of extensions, even if there's only one extension module
                return _.values(arguments);
            });
            this.processOptions(options);
            this._deferredInit();
            $.when(modulesPromise, providerPromise)
                .then(this._init.bind(this))
                .then(this._resolveDeferredInit.bind(this));
            SegmentComponent.__super__.initialize.call(this, options);
        },

        _init: function(extensions, provider) {
            this.dataProvider = provider;

            _.each(extensions, function(extension) {
                extension.load(this);
            }, this);

            this.initStorage();
            this.initEntityChangeEvents();
            this.setupDataProvider();
            this.initGrouping();
            this.initDateGrouping();
            this.initColumn();
            var promise = this.configureFilters();

            this.form = this.$storage.parents('form');
            this.form.submit(_.bind(this.onBeforeSubmit, this));

            return promise;
        },

        initStorage: function() {
            this.$storage = $(this.options.valueSource);
            this.$storage.on('change.' + this.cid, function() {
                this.trigger('updateData', this.load());
            }.bind(this));
        },

        initEntityChangeEvents: function() {
            var $entityChoice = $(this.options.entityChoice);
            this.entityClassName = $entityChoice.val();

            var handleEntityChange = function() {
                this.entityClassName = $entityChoice.val();
                var data = {};
                this.trigger('resetData', data);
                this.save(data);
                this.trigger('entityChange', this.entityClassName);
            }.bind(this);

            var onEntityChoiceChange = function(e) {
                if (this.entityClassName === $entityChoice.val()) {
                    // there's nothing to confirm
                    return;
                }

                var confirm;
                var oldVal = _.result(e.removed, 'id');
                var requiresConfirm = _.some(this.load() || [], function(value) {
                    return !_.isEmpty(value);
                });

                if (this.options.initEntityChangeEvents && requiresConfirm) {
                    confirm = new DeleteConfirmation({
                        title: __('Change Entity Confirmation'),
                        okText: __('Yes'),
                        content: __(this.options.entityChangeConfirmMessage)
                    });

                    confirm.on('ok', handleEntityChange);
                    confirm.on('cancel', function() {
                        $entityChoice.val(oldVal).change();
                    });
                    confirm.once('hidden', function() {
                        _.defer(confirm.dispose.bind(confirm));
                    });
                    confirm.open();
                } else {
                    handleEntityChange();
                }
            }.bind(this);

            $entityChoice.on('change', onEntityChoiceChange);
            this.once('dispose:before', function() {
                $entityChoice.off('change', onEntityChoiceChange);
            }, this);
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
                data = this.dataProvider.pathToEntityChainSafely(value);
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

        setupDataProvider: function() {
            if (this.options.dataProviderFilterPreset) {
                this.dataProvider.setFilterPreset(this.options.dataProviderFilterPreset);
            }
            this.dataProvider.setRootEntityClassName(this.entityClassName);
            this.on('entityChange', function(entityClassName) {
                this.dataProvider.setRootEntityClassName(entityClassName);
            });
        },

        /**
         * Initializes Fields Grouping component
         */
        initGrouping: function() {
            var options = this.options.grouping;
            var $table = $(options.itemContainer);
            var $editor = $(options.form);

            if (_.isEmpty($table) || _.isEmpty($editor) || !this.groupingFieldChoiceComponent) {
                // there's no grouping
                return;
            }

            this.groupingFieldChoiceComponent.view.setEntity(this.entityClassName);
            this.on('entityChange', function(entityClassName) {
                this.groupingFieldChoiceComponent.view.setEntity(entityClassName);
            });

            // prepare collection for Items Manager
            var collection = new EntityFieldsCollection(this.load('grouping_columns'), {
                model: GroupingModel,
                dataProvider: this.dataProvider
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

            this.once('before-submit', function() {
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
                        data.name = this.formatChoice(data.name, template);
                    } catch (e) {
                        data.name = __('oro.querydesigner.field_not_found');
                        data.deleted = true;
                    }
                    return tmpl(data);
                }.bind(this),
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

            this.dateGroupingFieldChoiceComponent.view.setEntity(this.entityClassName);
            this.on('entityChange', function(entityClassName) {
                this.dateGroupingFieldChoiceComponent.view.setEntity(entityClassName);
            });
        },

        /**
         * Initializes Columns component
         */
        initColumn: function() {
            var options = this.options.column;
            var metadata = this.options.metadata;
            var $table = $(options.itemContainer);
            var $form = $(options.form);

            if (_.isEmpty($form) || !this.columnFieldChoiceComponent) {
                // there's no columns
                return;
            }

            // setup FieldChoice of Items Manager Editor
            this.columnFieldChoiceComponent.view.setEntity(this.entityClassName);
            this.on('entityChange', function(entityClassName) {
                this.columnFieldChoiceComponent.view.setEntity(entityClassName);
            });

            var functionChoiceView = null;
            if (this.columnFunctionChoiceComponent) {
                functionChoiceView = this.columnFunctionChoiceComponent.view;
            }
            this.columnFormView = new ColumnFormView({
                el: $form,
                autoRender: true,
                fieldChoiceView: this.columnFieldChoiceComponent.view,
                functionChoiceView: functionChoiceView
            });

            var $editor = this.columnFormView.$el;

            // prepare collection for Items Manager
            var collection = new EntityFieldsCollection(this.load('columns'), {
                model: ColumnModel,
                dataProvider: this.dataProvider
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

            this.once('before-submit', function() {
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
                        data.name = this.formatChoice(data.name, template);
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
                }.bind(this),
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
                return $.when();
            }

            this.conditionBuilderComponent.setEntity(this.entityClassName);
            this.on('entityChange', function(entityClassName) {
                this.conditionBuilderComponent.setEntity(entityClassName);
            });

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

            return $.when(this.conditionBuilderComponent.view.getDeferredRenderPromise());
        }
    }, {
        INVALID_DATA_ISSUE: 'INVALID_DATA',
        UNSAVED_CHANGES_ISSUE: 'UNSAVED_CHANGES'
    });

    return SegmentComponent;
});
