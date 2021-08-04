define(function(require) {
    'use strict';

    const BaseComponent = require('oroui/js/app/components/base/component');
    const routing = require('routing');
    const _ = require('underscore');
    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    require('jquery.select2');

    const SecurityAccessLevelsComponent = BaseComponent.extend({
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

        /**
         * @inheritdoc
         */
        constructor: function SecurityAccessLevelsComponent(options) {
            SecurityAccessLevelsComponent.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            const self = this;
            this.dataCache = {};
            this.options = _.extend({}, this.defaultOptions, options);
            this.element = options._sourceElement;
            this.element.find(this.options.accessLevelFieldSelector).each(function() {
                const $field = $(this);
                const $input = $field.find('input');
                const permissionName = $field.siblings('input').val();
                const oid = $field.attr(self.options.objectIdentityAttribute);
                const url = routing.generate(self.options.accessLevelRoute, {oid: oid.replace(/\\/g, '_'), permission: permissionName});
                $input.inputWidget('create', 'select2', {
                    initializeOptions: {
                        initSelection: function(element, callback) {
                            callback({id: $input.val(), text: $input.data('valueText')});
                        },
                        query: self._select2Query.bind(self, url),
                        minimumResultsForSearch: -1
                    }
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
            const self = this;
            if (url in this.dataCache) {
                query.callback({results: this.dataCache[url]});
            } else {
                $.ajax({
                    url: url,
                    success: function(data) {
                        const options = [];
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
         * @inheritdoc
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
