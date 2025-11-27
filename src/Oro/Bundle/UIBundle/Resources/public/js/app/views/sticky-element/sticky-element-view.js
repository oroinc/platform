import $ from 'jquery';
import _ from 'underscore';
import BaseView from 'oroui/js/app/views/base/view';
import stickyElementMixin from 'oroui/js/app/views/sticky-element/sticky-element-mixin';

const StickyElementView = BaseView.extend(_.extend({}, stickyElementMixin, {
    /**
     * @inheritdoc
     */
    constructor: function StickyElementView(options) {
        StickyElementView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        StickyElementView.__super__.initialize.call(this, options);

        this.initializeSticky({
            $stickyElement: $(options.el),
            stickyOptions: options.stickyOptions || {}
        });
    },

    /**
     * @inheritdoc
     */
    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.disposeSticky();

        StickyElementView.__super__.dispose.call(this);
    }
}));

export default StickyElementView;
