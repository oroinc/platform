/**
 * Displays header of board column
 * @augments BaseView
 */
import BaseView from 'oroui/js/app/views/base/view';
import HintView from 'orodatagrid/js/app/views/hint-view';
import template from 'tpl-loader!../../../../templates/board/column-header-view.html';

const ColumnHeaderView = BaseView.extend({
    /**
     * @inheritdoc
     */
    className: 'board-column-header',

    /**
     * @inheritdoc
     */
    template,

    events: {
        mouseenter: 'onMouseEnter',
        mouseleave: 'onMouseLeave'
    },

    /**
     * @inheritdoc
     */
    constructor: function ColumnHeaderView(options) {
        ColumnHeaderView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.boardCollection = options.boardCollection;
        this.listenTo(this.boardCollection, 'add remove reset sort', this.markIfEmpty);
        ColumnHeaderView.__super__.initialize.call(this, options);
        this.markIfEmpty();
    },

    /**
     * Add empty css class to root element if modal is empty
     */
    markIfEmpty: function() {
        this.$el.toggleClass('empty', this.model.get('items').length === 0);
    },

    /**
     * Mouse Enter on column name to show popover
     *
     * @param {Event} e
     */
    onMouseEnter: function(e) {
        this.subview('hint', new HintView({
            el: this.$('[data-grid-header-cell-label]'),
            offsetOfEl: this.$el,
            autoRender: true,
            popoverConfig: {
                content: this.model.get('label')
            }
        }));

        this.hintTimeout = setTimeout(function() {
            const hint = this.subview('hint');

            if (hint && !hint.fullLabelIsVisible()) {
                hint.show();
            }
        }.bind(this), 300);
    },

    /**
     * Mouse Leave from column name to hide popover
     *
     * @param {Event} e
     */
    onMouseLeave: function(e) {
        clearTimeout(this.hintTimeout);
        if (this.subview('hint')) {
            this.removeSubview('hint');
        }
    }
});

export default ColumnHeaderView;
