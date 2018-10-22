define(function(require) {
    'use strict';

    var WidgetPickerItemView;
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetPickerItemView = BaseView.extend({
        template: require('tpl!oroui/templates/widget-picker/widget-picker-item-view.html'),
        tagName: 'div',
        className: 'widget-picker__item',

        events: {
            'click [data-role="description-toggler"]': '_toggleWidget',
            'click [data-role="add-action"]': '_onClickAddWidget'
        },

        listen: {
            'change:added model': '_changeAddedCount',
            'start_loading': '_addLoadingClassToBtnWrapper',
            'block_add_btn': '_blockAddBtn',
            'unblock_add_btn': '_unblockAddBtn'
        },

        /**
         * @inheritDoc
         */
        constructor: function WidgetPickerItemView() {
            WidgetPickerItemView.__super__.constructor.apply(this, arguments);
        },

        _blockAddBtn: function() {
            this.$('[data-role="add-action"]').addClass('disabled');
        },

        _unblockAddBtn: function() {
            this.$('[data-role="add-action"]').removeClass('disabled');
        },

        _addLoadingClassToBtnWrapper: function() {
            this.$el.addClass('loading');
        },

        setFilterModel: function(filterModel) {
            this.filterModel = filterModel;
            this.listenTo(this.filterModel, 'change:search', this.render);
        },

        _changeAddedCount: function() {
            this.$el.removeClass('loading');
            if (this.model.get('added') !== 0) {
                this.$('[data-role="added-badge"]').removeClass('hide');
            }
            this.$('[data-role="added-count"]').text('(' + this.model.get('added') + ')');
        },

        /**
         *
         * @param {Event} e
         * @protected
         */
        _onClickAddWidget: function(e) {
            e.preventDefault();
            this.trigger('widget_add', this.model, this);
        },

        /**
         *
         * @param {Event} e
         * @protected
         */
        _toggleWidget: function(e) {
            e.preventDefault();
            this.$('[data-role="description-toggler"]').toggleClass('collapsed');
            this.$('[data-role="description"]').slideToggle();
        }
    });

    return WidgetPickerItemView;
});
