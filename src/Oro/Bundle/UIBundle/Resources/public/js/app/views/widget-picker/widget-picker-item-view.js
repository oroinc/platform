define(function(require) {
    'use strict';

    const BaseView = require('oroui/js/app/views/base/view');

    const WidgetPickerItemView = BaseView.extend({
        template: require('tpl-loader!oroui/templates/widget-picker/widget-picker-item-view.html'),
        tagName: 'details',
        className: 'widget-picker__item',

        events: {
            'click [data-role="add-action"]': '_onClickAddWidget',
            'click': '_toggleWidget'
        },

        listen: {
            'change:added model': '_changeAddedCount',
            'start_loading': '_addLoadingClassToBtnWrapper',
            'block_add_btn': '_blockAddBtn',
            'unblock_add_btn': '_unblockAddBtn'
        },

        /**
         * @inheritdoc
         */
        constructor: function WidgetPickerItemView(options) {
            WidgetPickerItemView.__super__.constructor.call(this, options);
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
            if (window.$(e.target).data('role') === 'description-toggler' ||
                window.$(e.target).parents('[data-role="description-toggler"]').length) {
                const isOpen = this.$el.prop('open');

                if (isOpen) {
                    this.$('[data-role="description"]').slideUp(400, () => {
                        this.$el.removeAttr('open');
                    });
                } else {
                    this.$el.attr('open', true);
                    this.$('[data-role="description"]').hide().slideDown();
                }
            }
        }
    });

    return WidgetPickerItemView;
});
