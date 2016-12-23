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
         * @property {String}
         */
        groupSelector: '[data-attribute-group]',

        /**
         * @property {String}
         */
        removeBtn: '.removeRow.btn-link',

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            AttributeGroupComponent.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);
            mediator.on('attribute-select:find-selected-attributes', this.onGetSelectedAttributes, this);

            var groups = this.getAllGroups();
            if (groups.length === 1) {
                this.hideRemoveButton(groups);
            } else {
                this.showRemoveButton(groups);
            }
            //temporary width fix
            $(this.options._sourceElement).parents().find('.oro-item-collection .row-oro').width(960);
        },

        getAllGroups: function() {
            return $(this.groupSelector);
        },

        showRemoveButton: function(groups) {
            groups.parent().find('.removeRow.btn-link').show();
        },

        hideRemoveButton: function(groups) {
            groups.parent().find('.removeRow.btn-link').hide();
        },

        getAttributeSelect: function() {
            return $(this.options._sourceElement).find('[data-name="field__attribute-relations"]');
        },

        onGetSelectedAttributes: function(eventData) {
            var groupLabel = $(this.options._sourceElement).find('[data-attribute-select-group]').val();

            eventData.attributeSelects.push({groupLabel: groupLabel, attributesSelect: this.getAttributeSelect()});
        },

        dispose: function() {
            var groups = this.getAllGroups();
            if (groups.length === 2) { // Disable remove button for the last group
                this.hideRemoveButton(groups);
            }

            mediator.off('attribute-select:find-selected-attributes', this.onGetSelectedAttributes, this);
            mediator.trigger('attribute-group:remove', {
                attributeSelectFtid: this.getAttributeSelect().data('ftid'),
                firstGroup: groups.not(this.options._sourceElement).first()
            });

            AttributeGroupComponent.__super__.dispose.call(this);
        }
    });

    return AttributeGroupComponent;
});
