define(function(require) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const DialogWidget = require('oro/dialog-widget');
    const actionsTemplate = require('tpl-loader!orofilter/templates/filters-dialog-actions.html');

    /**
     * @class FilterDialogWidget
     * @extends DialogWidget
     */
    const FilterDialogWidget = DialogWidget.extend({
        /**
         * @property {Function}
         */
        actionsTemplate: actionsTemplate,

        /**
         * @property {Object}
         */
        content: null,

        /**
         * @property {Object}
         */
        dialogOptions: {
            autoResize: false,
            modal: true,
            resize: false,
            dialogClass: 'filter-box'
        },

        /**
         * @inheritdoc
         */
        constructor: function FilterDialogWidget(options) {
            FilterDialogWidget.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            if (_.isEmpty(options.content)) {
                throw new TypeError('"content" property should be not empty');
            }
            _.extend(this, _.pick(options, ['content']));

            options.dialogOptions = _.extend({}, this.dialogOptions, options.dialogOptions);

            FilterDialogWidget.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        render: function() {
            this.$placeholder = $('<div />');
            this.content.after(this.$placeholder);
            this.$el.append(this.content);
            this.$el.append(this.actionsTemplate());

            FilterDialogWidget.__super__.render.call(this);

            this._bindActionEvents();
        },

        /**
         * Trigger for reset all filters
         * @param {jQuery.Event} e
         */
        onResetAll: function(e) {
            mediator.trigger('filters:reset', e);
        },

        /**
         * Bind action for action in dialog-widget
         */
        _bindActionEvents: function() {
            this.actionsEl.on('click', '[data-role="reset-filters"]', this.onResetAll.bind(this));
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            this.$placeholder.before(this.content);

            this.$placeholder.remove();

            FilterDialogWidget.__super__.dispose.call(this);
        }
    });

    return FilterDialogWidget;
});
