define(function(require) {
    'use strict';

    var SelectRowCell;
    var $ = require('jquery');
    var BaseView = require('oroui/js/app/views/base/view');
    var Backgrid = require('backgrid');
    var template = require('tpl!orodatagrid/templates/datagrid/select-row-cell.html');

    /**
     * Renders a checkbox for row selection.
     *
     * @export  oro/datagrid/cell/select-row-cell
     * @class   oro.datagrid.cell.SelectRowCell
     * @extends BaseView
     */
    SelectRowCell = BaseView.extend({
        /** @property */
        className: 'select-row-cell renderable',

        /** @property */
        tagName: 'td',

        /** @property */
        template: template,

        /** @property */
        checkboxSelector: '[data-role="select-row-cell"]',

        /** @property */
        events: {
            'change :checkbox': 'onChange',
            'click': 'updateCheckbox'
        },

        /**
         * @inheritDoc
         */
        constructor: function SelectRowCell() {
            SelectRowCell.__super__.constructor.apply(this, arguments);
        },

        /**
         * Initializer. If the underlying model triggers a `select` event, this cell
         * will change its checked value according to the event's `selected` value.
         *
         * @param {Object} options
         * @param {Backgrid.Column} options.column
         * @param {Backbone.Model} options.model
         */
        initialize: function(options) {
            this.column = options.column;
            if (!(this.column instanceof Backgrid.Column)) {
                this.column = new Backgrid.Column(this.column);
            }

            this.template = this.getTemplateFunction();

            this.listenTo(this.model, 'backgrid:select', function(model, checked) {
                this.$(':checkbox').prop('checked', checked).change();
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }
            delete this.column;
            delete this.$checkbox;
            SelectRowCell.__super__.dispose.apply(this, arguments);
        },

        /**
         * Focuses the checkbox.
         *
         * @param e
         */
        updateCheckbox: function(e) {
            if (this.$checkbox.get(0) !== e.target && !$(e.target).closest('label').length) {
                this.$checkbox.prop('checked', !this.$checkbox.prop('checked')).change();
            }
            e.stopPropagation();
        },

        /**
         * When the checkbox's value changes, this method will trigger a Backbone
         * `backgrid:selected` event with a reference of the model and the
         * checkbox's `checked` value.
         */
        onChange: function(e) {
            this.model.trigger('backgrid:selected', this.model, $(e.target).prop('checked'));
        },

        /**
         * Renders a checkbox in a table cell.
         */
        render: function() {
            // work around with trigger event to get current state of model (selected or not)
            var state = {selected: false};
            this.model.trigger('backgrid:isSelected', this.model, state);
            this.$el.html(this.template({
                checked: state.selected
            }));
            this.$checkbox = this.$el.find(this.checkboxSelector);

            this.$checkbox.inputWidget('isInitialized')
                ? this.$checkbox.inputWidget('refresh')
                : this.$checkbox.inputWidget('create');
            return this;
        }
    });

    return SelectRowCell;
});
