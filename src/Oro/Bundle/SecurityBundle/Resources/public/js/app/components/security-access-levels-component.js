define(function(require) {
    'use strict';

    var SecurityAccessLevelsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    require('jquery.select2');

    SecurityAccessLevelsComponent = BaseComponent.extend({
        element: null,

        defaultOptions: {
            accessLevelFieldSelector: '.access_level_value',
            accessLevelLinkSelector: '.access_level_value a',
            selectDivSelector: '.access_level_value_choice',
            linkDivSelector: 'access_level_value_link',
            accessLevelRoute: 'oro_security_access_levels',
            objectIdentityAttribute: 'data-identity',
            selectorNameAttribute: 'data-selector-name',
            selectorIdAttribute: 'data-selector-id',
            valueAttribute: 'data-value'
        },

        dataCache: null,

        options: {},

        initialize: function(options) {
            var self = this;
            this.dataCache = {};
            this.options = _.extend({}, this.defaultOptions, options);
            this.element = options._sourceElement;
            this.element.find(this.options.accessLevelFieldSelector).each(function() {
                var $field = $(this);
                var $input = $field.find('input');
                var permissionName = $field.siblings('input').val();
                var oid = $field.attr(self.options.objectIdentityAttribute);
                var url = routing.generate(self.options.accessLevelRoute, {oid: oid.replace(/\\/g, '_')});
                $input.select2({
                    initSelection: function(element, callback) {
                        callback({id: $input.val(), text: $input.data('valueText')});
                    },
                    query: _.bind(self._select2Query, self, url),
                    minimumResultsForSearch: -1
                }).on('change.' + self.cid, function(e) {
                    mediator.trigger('securityAccessLevelsComponent:link:click', {
                        accessLevel: e.val,
                        identityId: oid,
                        permissionName: permissionName
                    });
                });
            });
        },

        _select2Query: function(url, query) {
            var self = this;
            if (url in this.dataCache) {
                query.callback({results: this.dataCache[url]});
            } else {
                $.ajax({
                    url: url,
                    success: function(data) {
                        var options = [];
                        _.each(_.omit(data, 'template_name'), function(val, key) {
                            options.push({id: key, text: val});
                        });
                        self.dataCache[url] = options;
                        query.callback({results: options});
                    }
                });
            }
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.element) {
                this.element.find(this.options.accessLevelFieldSelector).each(function() {
                    $(this).find('input').off('change.' + this.cid).inputWidget('dispose');
                });
            }

            SecurityAccessLevelsComponent.__super__.dispose.call(this);
        }
    });

    return SecurityAccessLevelsComponent;
});
