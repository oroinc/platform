import BaseView from 'oroui/js/app/views/base/view';
import _ from 'underscore';
import $ from 'jquery';
import mediator from 'oroui/js/mediator';

const LocalizationSelectView = BaseView.extend({
    /**
     * @property {Object}
     */
    options: {
        selectSelector: 'select',
        useParentSelector: 'input[type="checkbox"]'
    },

    /**
     * @property {jQuery.Element}
     */
    $select: null,

    /**
     * @property {jQuery.Element}
     */
    $useParent: null,

    /**
     * @inheritdoc
     */
    constructor: function LocalizationSelectView(options) {
        LocalizationSelectView.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.extend({}, this.options, options);

        this.$select = this.$el.find(this.options.selectSelector);
        this.$useParent = this.$el.find(this.options.useParentSelector);
        this.$useParent.on('change' + this.eventNamespace(), this.onUseParentChange.bind(this));

        mediator.on('enabled_localizations:changed', this.onEnabledLocalizationsChanged, this);
    },

    /**
     * @inheritdoc
     */
    dispose: function(options) {
        if (this.disposed) {
            return;
        }

        this.$useParent.off('change' + this.eventNamespace());
        mediator.off(null, null, this);

        LocalizationSelectView.__super__.dispose.call(this);
    },

    /**
     * @param {Object} data
     */
    onEnabledLocalizationsChanged: function(data) {
        const select = this.$select;
        const selected = select.val();

        select.find('option[value!=""]').remove().val('').trigger('change');

        _.each(data, function(localization) {
            select.append($('<option></option>').attr('value', localization.id).text(localization.label));
        });

        if (selected) {
            select.val(selected);

            if (selected !== select.val()) {
                select.val('');
            }
        }

        select.trigger('change');
    },

    onUseParentChange: function() {
        mediator.trigger('default_localization:use_parent_scope', this.$useParent.is(':checked'));
    }
});

export default LocalizationSelectView;
