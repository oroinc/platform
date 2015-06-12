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
            var $el = options._sourceElement,
                select2Config = options.configs,
                extraModules = {},
                perPage = options.configs.per_page || 10;
            if ('extra_modules' in select2Config) {
                _.each(select2Config.extra_modules, function (item) {
                    extraModules[item.name] = require(item.path);
                });
            }

            if (select2Config.component || select2Config.extra_config) {
                debugger;
            }

            this.processExtraConfig(select2Config, options.url, perPage, $el);

            orderHandler.handle(select2Config, options.url, perPage, $el);


            var configurator = new Select2Config(
                select2Config,
                options.url
            );

            select2Config = configurator.getConfig(options.excluded || [], perPage);

            $el.select2(select2Config);

            $el.trigger('select2-init');
        },

        processExtraConfig: function (select2Config) {
            return select2Config;
        },

        dispose: function () {

        }
    });

    return Select2Component;
});
