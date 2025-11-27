import $ from 'jquery';
import _ from 'underscore';
import mediator from 'oroui/js/mediator';
import BaseView from 'oroui/js/app/views/base/view';

const ThemeConfigurationDynamicRender = BaseView.extend({
    events: {
        'change [data-role="dynamic-render"]': 'onThemeChange'
    },

    selectors: null,

    defaults: {
        selectors: {
            form: 'form:first'
        }
    },

    /**
     * @inheritdoc
     */
    constructor: function ThemeConfigurationDynamicRender(options) {
        _.extend(this, this.defaults, _.pick(options, 'selectors'));

        ThemeConfigurationDynamicRender.__super__.constructor.call(this, options);
    },

    onThemeChange: function() {
        mediator.execute('showLoading');

        const $form = this.$(this.selectors.form);
        const data = $form.serializeArray();
        data.push({name: 'reloadWithoutSaving', value: true});

        mediator.execute('submitPage', {
            url: $form.attr('action'),
            type: $form.attr('method'),
            data: $.param(data)
        });
    }
});

export default ThemeConfigurationDynamicRender;
