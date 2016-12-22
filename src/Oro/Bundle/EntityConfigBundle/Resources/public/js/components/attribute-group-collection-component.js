define(function(require) {
    'use strict';

    var AttributeGroupComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');

    AttributeGroupComponent = BaseComponent.extend({

        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            mediator.on('attribute-select:find-selected-attributes', this.onGetSelectedAttributes, this);
            //Remove delete button for default group
            if ($(this.options._sourceElement).find('[data-is-default]').data('is-default')) {
                $(this.options._sourceElement).parent().find('.removeRow.btn-link').remove();
            }
            //temporary width fix
            $(this.options._sourceElement).parents().find('.oro-item-collection .row-oro').width(960);
        },

        onGetSelectedAttributes: function(eventData) {
            var groupLabel = $(this.options._sourceElement).find('[data-attribute-select-group]').val();

            var attributesSelect = $(this.options._sourceElement).find('[data-name="field__attribute-relations"]');
            if ($(attributesSelect).data('ftid') === eventData.sourceFtid) {
                return;
            }
            $(attributesSelect).find('option:selected').each(function() {
                var val = $(this).val();
                eventData.selectedOptions[val] = groupLabel;
            });
        }
    });

    return AttributeGroupComponent;
});
