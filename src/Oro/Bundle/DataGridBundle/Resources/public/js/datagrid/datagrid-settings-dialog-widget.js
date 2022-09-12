define(function(require) {
    'use strict';

    const _ = require('underscore');
    const DialogWidget = require('oro/dialog-widget');
    const actionsTemplate =
        require('tpl-loader!orodatagrid/templates/datagrid-settings/datagrid-settings-dialog-widget-actions.html');
    const mediator = require('oroui/js/mediator');

    /**
     * @class DatagridSettingsDialogWidget
     * @extends DialogWidget
     */
    const DatagridSettingsDialogWidget = DialogWidget.extend({
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
         * @property {Function}
         */
        actionsTemplate: actionsTemplate,

        /**
         * @property {Object}
         */
        dialogOptions: {
            autoResize: false,
            modal: true,
            resize: false,
            dialogClass: 'datagrid-settings-dialog',
            close: function() {
                mediator.trigger('dropdown-launcher:hide');
            }
        },

        /**
         * @inheritdoc
         */
        constructor: function DatagridSettingsDialogWidget(options) {
            DatagridSettingsDialogWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         * @param options
         */
        initialize: function(options) {
            if (!_.isFunction(options.View)) {
                throw new TypeError('"View" property should be the function');
            }
            _.extend(this, _.pick(options, ['View', 'viewOptions']));

            options.dialogOptions = _.extend({}, this.dialogOptions, options.dialogOptions);

            DatagridSettingsDialogWidget.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this.viewOptions._sourceElement = this.$el;
            this.viewOptions.title = '';
            this.subview('datagridSettingsView', new this.View(this.viewOptions));
            if (_.isFunction(this.subview('datagridSettingsView').beforeOpen)) {
                this.subview('datagridSettingsView').beforeOpen();
            }
            this.$el.append(this.actionsTemplate());

            DatagridSettingsDialogWidget.__super__.render.call(this);
        },

        /**
         * @instance
         */
        onContentUpdated: function() {
            if (_.isFunction(this.subview('datagridSettingsView').updateViews)) {
                this.subview('datagridSettingsView').updateViews();
            }
            this.$el.focusFirstInput();
        },

        /**
         * @instance
         */
        hide: function() {
            this.loadingBar.appendTo('#oroplatform-header');

            DatagridSettingsDialogWidget.__super__.hide.call(this);
        }
    });

    return DatagridSettingsDialogWidget;
});
