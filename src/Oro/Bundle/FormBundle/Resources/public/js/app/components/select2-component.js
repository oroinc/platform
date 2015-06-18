define(function (require) {
    'use strict';

    var Select2Component,
        $ = require('jquery'),
        _ = require('underscore'),
        __ = require('orotranslation/js/translator'),
        Select2 = require('jquery.select2'),
        Select2Config = require('oroform/js/select2-config'),
        orderHandler = require('oroform/js/select2_relevancy_order_handler'),
        BaseComponent = require('oroui/js/app/components/base/component');
    Select2Component = BaseComponent.extend({
        /**
         * @constructor
         * @param {Object} options
         */
        initialize: function (options) {
            var select2Config = options.configs,
                extraModules = {},
                params = {
                    $el: options._sourceElement,
                    perPage: options.configs.per_page || 10,
                    url: options.url,
                    value: _.result(options, 'value', false),
                    suggestions: _.result(options, 'suggestions', false),
                    oroTagCreateGranted: _.result(options, 'oro_tag_create_granted', false),
                    channelId: _.result(options, 'channel_id', ''),
                    channelFieldName: _.result(options, 'channel_field_name', ''),
                    marketingListId: _.result(options, 'marketing_list_id', '')
                };
            if ('extra_modules' in select2Config) {
                _.each(select2Config.extra_modules, function (item) {
                    extraModules[item.name] = require(item.path);
                });
            }
            console.log('Select2 component = ' + _.result(select2Config, 'component'))

            this.processExtraConfig(select2Config, params);

            orderHandler.handle(select2Config, params.url, params.perPage, params.$el);


            var configurator = new Select2Config(
                select2Config,
                options.url
            );

            select2Config = configurator.getConfig(options.excluded || [], params.perPage);

            params.$el.select2(select2Config);

            params.$el.trigger('select2-init');
        },

        processExtraConfig: function (select2Config) {
            return select2Config;
        },

        dispose: function () {

        }
    });

    return Select2Component;
});
