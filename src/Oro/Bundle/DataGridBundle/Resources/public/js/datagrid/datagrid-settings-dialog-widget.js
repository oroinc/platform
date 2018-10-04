define(function(require) {
    'use strict';

    var DatagridSettingsDialogWidget;
    var _ = require('underscore');
    var DialogWidget = require('oro/dialog-widget');
    var actionsTemplate = require('tpl!orodatagrid/templates/datagrid-settings/datagrid-settings-dialog-widget-actions.html');

    /**
     * @class DatagridSettingsDialogWidget
     * @extends DialogWidget
     */
    DatagridSettingsDialogWidget = DialogWidget.extend({
        /**
         * View constructor
         * @property {Constructor.View}
         */
        View: null,

        /**
         * view instance
         * @property {View}
         */
        view: null,

        /**
         * view constructor options
         * @property {Object}
         */
        viewOptions: {},

        /**
         * @property {underscore.template}
         */
        actionsTemplate: actionsTemplate,

        /**
         * Loading bar view instance
         */
        loadingBar: null,

        /**
         * @inheritDoc
         */
        constructor: function DatagridSettingsDialogWidget() {
            DatagridSettingsDialogWidget.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         * @param options
         */
        initialize: function(options) {
            if (!_.isFunction(options.View)) {
                throw new TypeError('"View" property should be the function');
            }
            _.extend(this, _.pick(options, ['View', 'viewOptions']));

            options.dialogOptions = {
                autoResize: false,
                modal: true,
                resize: false,
                dialogClass: 'datagrid-settings-dialog'
            };

            DatagridSettingsDialogWidget.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        render: function() {
            this.$el.append(this.actionsTemplate());
            this.viewOptions._sourceElement = this.$el;
            this.viewOptions.title = '';
            this.view = new this.View(this.viewOptions);
            this.view.beforeOpen();

            DatagridSettingsDialogWidget.__super__.render.call(this);
        },

        /**
         * @instance
         */
        onContentUpdated: function() {
            this.view.updateViews();
            this.$el.focusFirstInput();
        },

        /**
         * @instance
         */
        hide: function() {
            this.loadingBar.appendTo('#oroplatform-header');
            DatagridSettingsDialogWidget.__super__.hide.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            this.view.dispose();
            DatagridSettingsDialogWidget.__super__.dispose.call(this);
        }
    });

    return DatagridSettingsDialogWidget;
});
