define(function(require) {
    'use strict';

    var SecurityAccessLevelsComponent;
    var BaseComponent = require('oroui/js/app/components/base/component');
    var routing = require('routing');
    var _ = require('underscore');
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');

    SecurityAccessLevelsComponent = BaseComponent.extend({
        element: null,

        defaultOptions: {
            accessLevelLinkSelector: '.access_level_value a',
            selectDivSelector: '.access_level_value_choice',
            linkDivSelector: 'access_level_value_link',
            accessLevelRoute: 'oro_security_access_levels',
            objectIdentityAttribute: 'data-identity',
            selectorNameAttribute: 'data-selector-name',
            selectorIdAttribute: 'data-selector-id',
            valueAttribute: 'data-value'
        },

        options: {},

        selectTemplate: _.template(
            '<select name="<%= name %>" id="<%= id %>" class="<%= className %>">' +
                '<% $.each(options, function (value, text) { %>' +
                    '<option value="<%= value %>"' +
                        '<% if (value == selectedOption) { %> selected="selected"<% } %>' +
                    '><%= text %></option>' +
                '<% }) %>' +
            '</select>'
        ),

        initialize: function(options) {
            this.options = _.extend({}, this.defaultOptions, options);

            this.element = options._sourceElement;

            var self = this;
            this.element.on('click.' + this.cid, self.options.accessLevelLinkSelector, function() {
                var link = $(this);
                var parentDiv = link.parents('.access_level_value').first();
                var selectDiv = parentDiv.find(self.options.selectDivSelector);
                var linkDiv = parentDiv.find(self.options.linkDivSelector);
                link.hide();
                var originOid = parentDiv.attr(self.options.objectIdentityAttribute);
                var oid = originOid.replace(/\\/g, '_');
                $.ajax({
                    url: routing.generate(self.options.accessLevelRoute, {oid: oid}),
                    success: function(data) {
                        var selector = $(self.selectTemplate({
                            name: parentDiv.attr(self.options.selectorNameAttribute),
                            id: parentDiv.attr(self.options.selectorIdAttribute),
                            className: 'security-permission',
                            options: _.omit(data, 'template_name'),
                            selectedOption: parentDiv.attr(self.options.valueAttribute)
                        }));

                        selectDiv.append(selector);
                        selectDiv.show();
                        linkDiv.remove();
                        selector.inputWidget('refresh');
                        selector.on('change', function(e) {
                            mediator.trigger('securityAccessLevelsComponent:link:click', {
                                accessLevel: $(e.target).val(),
                                identityId: originOid,
                                permissionName: parentDiv.next().val()
                            });
                        });
                    },
                    error: function() {
                        link.show();
                    }
                });

                return false;
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            if (this.element) {
                this.element.off('.' + this.cid);
            }

            SecurityAccessLevelsComponent.__super__.dispose.call(this);
        }
    });

    return SecurityAccessLevelsComponent;
});
