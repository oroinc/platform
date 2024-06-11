define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseView = require('oroui/js/app/views/base/view');

    const FormAjaxReloadingView = BaseView.extend({
        defaultsSelectors: {
            form: 'form:first'
        },

        events() {
            const events = {};

            if (this.defaults.listenChangeElements) {
                _.forEach(this.defaults.listenChangeElements, function(selector) {
                    events['change ' + selector] = this.onChange;
                }, this);
            }

            return events;
        },

        /**
         * @inheritdoc
         */
        constructor: function FormAjaxReloadingView(options) {
            const selectors = _.pick(options, 'selectors')['selectors'] || {};

            this.defaults = {...this.defaultsSelectors, ...selectors};
            FormAjaxReloadingView.__super__.constructor.call(this, options);
        },

        onChange: function() {
            mediator.execute('showLoading');

            const $form = this.$(this.defaults.form);
            const data = $form.serializeArray();

            data.push({name: 'reloadWithoutSaving', value: true});

            mediator.execute('submitPage', {
                url: $form.attr('action'),
                type: $form.attr('method'),
                data: $.param(data)
            });
        }
    });

    return FormAjaxReloadingView;
});
