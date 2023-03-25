import __ from 'orotranslation/js/translator';
import {debounce} from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import template from 'tpl-loader!orodatagrid/templates/sort-rows-drag-n-drop/selection-state-hint.html';

import Popper from 'popper';

const SelectionStateHintView = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat([
        'referenceEl'
    ]),

    /**
     * @inheritdoc
     */
    template: template,

    /**
     * @inheritdoc
     */
    className: 'selection-state-hint hide',

    /**
     * @inheritdoc
     */
    events: {
        'click [data-role="reset"]': 'doClickReset'
    },

    /**
     * @inheritdoc
     */
    listen: {
        'change collection': 'toggle',
        'remove collection': 'toggle'
    },

    /**
     * @inheritdoc
     */
    constructor: function SelectionStateHintView(options) {
        this.toggle = debounce(this.toggle, 50);
        SelectionStateHintView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getTemplateData: function() {
        const data = SelectionStateHintView.__super__.getTemplateData.call(this);

        const length = this.collection.where(model => model.get('_selected')).length;
        data.text = __('oro.datagrid.drag_n_drop.items_selected', {count: length});
        data.buttonText = __('oro.datagrid.drag_n_drop.unselect_all');

        return data;
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.destroyPopper();
        delete this.collection;
        SelectionStateHintView.__super__.dispose.call(this);
    },

    initPopper() {
        this.destroyPopper();

        this.popper = new Popper(this.referenceEl, this.$el, {
            placement: 'bottom',
            positionFixed: true,
            removeOnDestroy: false,
            modifiers: {
                offset: {
                    offset: `0, -${Math.floor(this.$el.height())}`
                },
                preventOverflow: {
                    boundariesElement: 'scrollParent',
                    padding: 0
                },
                flip: {
                    enabled: false
                }
            }
        });
    },

    destroyPopper() {
        if (this.popper) {
            this.popper.destroy();
            delete this.popper;
        }
    },

    toggle() {
        if (this.disposed) {
            return;
        }

        const length = this.collection.where(model => model.get('_selected')).length;
        const toSow = length > 1;

        this.$el.toggleClass('hide', !toSow);

        if (toSow) {
            this.render();
            this.initPopper();
        } else {
            this.destroyPopper();
        }
    },

    /**
     * Click handler
     **/
    doClickReset() {
        this.trigger('reset');
    }
});

export default SelectionStateHintView;
