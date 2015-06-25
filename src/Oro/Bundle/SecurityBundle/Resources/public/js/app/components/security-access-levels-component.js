/*jslint nomen:true*/
/*global define*/
define(function (require) {
    'use strict';

    var SecurityAccessLevelsComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        routing = require('routing'),
        $ = require('jquery');

    SecurityAccessLevelsComponent = BaseComponent.extend({
        accessLevelLinkSelector : '.access_level_value a',
        selectDivSelector : '.access_level_value_choice',
        linkDivSelector : 'access_level_value_link',

        accessLevelRoute : 'oro_security_access_levels',

        objectIdentityAttribute : 'data-identity',
        selectorNameAttribute : 'data-selector-name',
        selectorIdAttribute : 'data-selector-id',
        valueAttribute : 'data-value',

        initialize: function (options) {
            var self = this;
            options._sourceElement.on('click', self.accessLevelLinkSelector, function () {
                var link = $(this);
                var parentDiv = link.parent().parent();
                var selectDiv = parentDiv.find(self.selectDivSelector);
                var linkDiv = parentDiv.find(self.linkDivSelector);
                link.hide();
                var oid = parentDiv.attr(self.objectIdentityAttribute);
                oid = oid.replace(/\\/g, '_');
                $.ajax({
                    url: routing.generate(self.accessLevelRoute, {oid: oid}),
                    success: function (data) {
                        var selector = $('<select>');
                        selector.attr('name', parentDiv.attr(self.selectorNameAttribute));
                        selector.attr('id', parentDiv.attr(self.selectorIdAttribute));
                        selector.attr('class', 'security-permission');
                        $.each(data, function (value, text) {
                            if (value !== 'template_name') {
                                var option = $('<option>').attr('value', value).text(text);
                                if (parentDiv.attr(self.valueAttribute) == value) {
                                    option.attr('selected', 'selected');
                                }
                                selector.append(option);
                            }
                        });
                        selectDiv.append(selector);
                        selectDiv.show();
                        linkDiv.remove();
                        $('select').uniform('update');
                    },
                    error: function () {
                        link.show();
                    }
                });

                return false;
            });
        }
    });

    return SecurityAccessLevelsComponent;
});
