define(function(require) {
    'use strict';

    var WidgetPickerItemView;
    var BaseView = require('oroui/js/app/views/base/view');

    WidgetPickerItemView = BaseView.extend({
        template: require('tpl!oroui/templates/widget-picker/widget-picker-item-view.html'),
        tagName: 'div',
        className: 'widget-picker-container',

        events: {
            'click .widget-picker-collapse': '_toggleWidget',
            'click .widget-picker-add-btn': '_onClickAddWidget'
        },

        listen: {
            'change:added model': '_changeAddedCount',
            'start_loading': '_addLoadingClassToBtnWrapper',
            'block_add_btn': '_blockAddBtn',
            'unblock_add_btn': '_unblockAddBtn'
        },

        _blockAddBtn: function() {
            this.$('.widget-picker-add-btn').addClass('disabled');
        },

        _unblockAddBtn: function() {
            this.$('.widget-picker-add-btn').removeClass('disabled');
        },

        _addLoadingClassToBtnWrapper: function() {
            this.$el.addClass('loading-widget-content');
        },

        setFilterModel: function(filterModel) {
            this.filterModel = filterModel;
            this.listenTo(this.filterModel, 'change:search', this.render);
        },

        _changeAddedCount: function() {
            this.$el.removeClass('loading-widget-content');
            if (this.model.get('added') === 1) {
                this.$('.added').removeClass('hidden');
            }
            this.$('.added > span').text('(' + this.model.get('added') + ')');
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
            this.$('.widget-picker-collapse').toggleClass('collapsed-state');
            this.$('.widget-picker-description').fadeToggle();
        }
    });

    return WidgetPickerItemView;
});
