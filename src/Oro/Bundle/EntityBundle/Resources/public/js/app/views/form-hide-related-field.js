import $ from 'jquery';
import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';

const HideRelatedFieldView = BaseView.extend({

    /**
     * @property {Object}
     */
    options: {
        trackedFields: '',
        hideField: ''
    },
    /**
     * @inheritdoc
     */
    constructor: function HideRelatedFieldView(options) {
        HideRelatedFieldView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);
        this.$trackedFieldScalarValue = $('[name="' + this.options.trackedFields + '[scalarValue]"]');
        this.$trackedFieldUseFallback = $('[name="' + this.options.trackedFields + '[useFallback]"]');
        this.$hideField = $('[name="' + this.options.hideField + '"]').closest('.control-group');

        this.updateVisibility = this.updateVisibility.bind(this);

        this.$trackedFieldScalarValue.on('change', this.updateVisibility);
        this.$trackedFieldUseFallback.on('change', this.updateVisibility);

        this.updateVisibility();
    },

    updateVisibility: function() {
        const scalarValue = this.$trackedFieldScalarValue.val() === '1';
        const useFallback = this.$trackedFieldUseFallback.is(':checked');

        if (!useFallback && scalarValue) {
            this.$hideField.show();
        } else {
            this.$hideField.hide();
        }
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.$trackedFieldScalarValue.off('change', this.updateVisibility);
        this.$trackedFieldUseFallback.off('change', this.updateVisibility);
    }
});

export default HideRelatedFieldView;
