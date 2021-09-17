define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const mediator = require('oroui/js/mediator');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const Modal = require('oroui/js/modal');

    const AttributeGroupComponent = BaseComponent.extend({
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
         * @inheritdoc
         */
        constructor: function AttributeGroupComponent(options) {
            AttributeGroupComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            AttributeGroupComponent.__super__.initialize.call(this, options);

            this.options = _.defaults(options || {}, this.options);
            mediator.on('attribute-select:find-selected-attributes', this.onGetSelectedAttributes, this);

            const groups = this.getAllGroups();
            if (groups.length === 1) {
                this.hideRemoveButton(groups);
            } else {
                this.showRemoveButton(groups);
            }
            const self = this;
            $(this.options._sourceElement).parent().find(this.removeBtn).on('click', function(event) {
                const systemAttributesSelected = self.getAttributeSelect().find('option[locked="locked"]:selected');
                if (systemAttributesSelected.length) {
                    self.showConfirmModal(this);
                    return false;
                }
                return true;
            });
        },

        showConfirmModal: function(removeBtn) {
            const confirmDialog = new Modal({
                title: _.__('oro.attribute.remove_confirmation_title'),
                content: _.__('oro.attribute.remove_confirmation_text'),
                className: 'modal oro-modal-danger',
                attributes: {
                    role: 'alertdialog'
                }
            });

            confirmDialog.on('ok', function() {
                let closest = '*[data-content]';
                if ($(removeBtn).data('closest')) {
                    closest = $(removeBtn).data('closest');
                }

                const item = $(removeBtn).closest(closest);
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
            const groupLabel = $(this.options._sourceElement).find('[data-attribute-select-group]').val();

            eventData.attributeSelects.push({groupLabel: groupLabel, attributesSelect: this.getAttributeSelect()});
        },

        dispose: function() {
            const groups = this.getAllGroups();
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
