define(function(require) {
    'use strict';

    var AttributeGroupComponent;
    var $ = require('jquery');
    var _ = require('underscore');
    var mediator = require('oroui/js/mediator');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var Modal = require('oroui/js/modal');

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
        constructor: function AttributeGroupComponent() {
            AttributeGroupComponent.__super__.constructor.apply(this, arguments);
        },

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
            var self = this;
            $(this.options._sourceElement).parent().find(this.removeBtn).on('click', function(event) {
                var systemAttributesSelected = self.getAttributeSelect().find('option[locked="locked"]:selected');
                if (systemAttributesSelected.length) {
                    self.showConfirmModal(this);
                    return false;
                }
                return true;
            });
            // temporary width fix
            $(this.options._sourceElement).parents().find('.oro-item-collection .row-oro').width(960);
        },

        showConfirmModal: function(removeBtn) {
            var confirmDialog = new Modal({
                title: _.__('oro.attribute.remove_confirmation_title'),
                content: _.__('oro.attribute.remove_confirmation_text'),
                className: 'modal oro-modal-danger'
            });

            confirmDialog.on('ok', function() {
                var item;
                var closest = '*[data-content]';
                if ($(removeBtn).data('closest')) {
                    closest = $(removeBtn).data('closest');
                }

                item = $(removeBtn).closest(closest);
                item.trigger('content:remove').remove();
                confirmDialog.close();
            });

            confirmDialog.open();
        },

        getAllGroups: function() {
            return $(this.groupSelector);
        },

        showRemoveButton: function(groups) {
            groups.parent().find(this.removeBtn).show();
        },

        hideRemoveButton: function(groups) {
            groups.parent().find(this.removeBtn).hide();
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
