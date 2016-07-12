define(function (require) {
    'use strict';

    var fieldAclComponent,
        BaseComponent = require('oroui/js/app/components/base/component'),
        LoadingMaskView = require('oroui/js/app/views/loading-mask-view'),
        routing = require('routing'),
        $ = require('jquery');

    fieldAclComponent = BaseComponent.extend({
        element: null,

        // container for loading mask overlay
        maskContainer: null,

        loadingMask: null,

        defaultOptions: {
            entityLabelSelector: '.entity-identity-label',
            accessLevelRoute:    'oro_field_acl_update',
            roleId: null
        },

        options: {},

        aclTemplate: _.template(
            '<tr><td></td><td colspan="5">' +
                '<table><tbody>' +
                '<tr>' +
                    '<th>&nbsp;</th>' +
                    '<% $.each(permissions, function (value, permisson_name) { %>' +
                    '<th width="16%"><%= permisson_name %></th></tr>' +
                    '<% }) %>' +
                '</tbody></table>' +
            '</td></tr>'
        ),

        /**
         * @param {Object} options
         */
        initialize: function (options) {
            this.options = _.extend({}, this.defaultOptions, options);
            this.element = options._sourceElement;
            delete options._sourceElement;

            // append mask container to the DOM
            this.maskContainer = $('<div></div>');
            this.element.append(this.maskContainer);
            this.loadingMask = new LoadingMaskView({container: this.maskContainer});

            var self = this;
            this.element.on('click.' + this.cid, self.options.entityLabelSelector, function() {
                var labelElement = $(this),
                    row = labelElement.parents('tr');

                self.showMaskAt(row);
                $.ajax({
                    url: routing.generate(self.options.accessLevelRoute, self.getURLParamsFromDOM(labelElement)),
                    success: function(data) {
                        self.hideMask();

                        row.after(data.toString());
                    },
                    error: function() {
                        self.hideMask();
                    }
                });

                return false;
            });

            fieldAclComponent.__super__.initialize.call(this, options);
        },

        /**
         * Shows loading mask over the row
         *
         * @param row
         */
        showMaskAt: function (row) {
            var rowPosition = row.position();

            // position overlay container correctly
            this.maskContainer.css({
                position: 'absolute',
                top:      rowPosition.top + row.scrollParent().scrollTop(),
                left:     rowPosition.left,
                width:    row.css('width'),
                height:   row.css('height'),
                display:  'block'
            });

            this.loadingMask.show();
        },

        hideMask: function () {
            this.loadingMask.hide();
            this.maskContainer.hide();
        },

        /**
         * Extract object identity from the DOM
         *
         * @param labelElement
         * @returns Object
         */
        getURLParamsFromDOM: function (labelElement) {
            var hiddenInput = labelElement.parent()
                .children('input[value^="entity:"]')
                .first();

            var entityName = hiddenInput
                .val()
                .replace(/\\/g, '_')
                .replace('entity:', '');

            return {id: this.options.roleId, className: entityName};
        },

        /**
         * Unload components with it's objects
         */
        dispose: function () {
            if (this.disposed) {
                // component is already removed
                return;
            }

            this.maskContainer.remove();
            delete this.maskContainer;
            delete this.loadingMask;
            delete this.element;

            fieldAclComponent.__super__.dispose.call(this);
        }
    });

    return fieldAclComponent;
});
