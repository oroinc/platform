define(function(require) {
    'use strict';

    const $ = require('jquery');
    const mediator = require('oroui/js/mediator');
    const _ = require('underscore');

    const Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    const AttributeSelectComponent = Select2AutocompleteComponent.extend({
        /**
         * @property {Object}
         */
        options: {
            delimiter: ';'
        },

        /**
         * @property {Object}
         */
        attributeSelect: null,

        /**
         * @property {Array}
         */
        oldOptionLabels: [],

        /**
         * @property {String}
         */
        ftid: null,

        /**
         * @inheritdoc
         */
        constructor: function AttributeSelectComponent(options) {
            AttributeSelectComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.attributeSelect = options._sourceElement;
            this.ftid = $(this.attributeSelect).data('ftid');
            const self = this;

            AttributeSelectComponent.__super__.initialize.call(this, options);

            // Modify dropdown - show already selected attributes (from other groups) in braces
            $(this.attributeSelect).on('select2-opening', function() {
                self.addLabelsToAttributesFromOtherGroups();
            });

            // Restore dropdown with initial option labels
            $(this.attributeSelect).on('select2-close', function() {
                self.restoreInitialDropdown();
            });

            $(this.attributeSelect).on('selected', function(e) {
                // Modify selected tag to show initial value (without braces)
                if (e.val in self.oldOptionLabels) {
                    const tag = $(self.attributeSelect).parent().find('.select2-choices li div').last();
                    $(tag).html(self.oldOptionLabels[e.val]);
                }
                const eventData = {
                    ftid: self.ftid,
                    selectedValue: e.val
                };
                mediator.trigger('attribute-select:selected', eventData);
            });

            mediator.on('attribute-select:selected', this.onAttributeSelect, this);
            mediator.on('attribute-group:remove', this.onGroupRemove, this);
        },

        /**
         * @param {Array} selectedOptions
         */
        prepareDropdown: function(selectedOptions) {
            if (selectedOptions.length) {
                for (const id in selectedOptions) {
                    if (selectedOptions.hasOwnProperty(id)) {
                        const option = $(this.attributeSelect).find('option[value="' + id + '"]');
                        const oldText = $(option).text();
                        const moveFrom = _.__('oro.attribute.move_from');
                        const groupName = selectedOptions[id] ? selectedOptions[id] : _.__('oro.attribute.noname');
                        const newText = oldText + ' (' + moveFrom + ' ' + groupName + ')';
                        $(option).text(newText);
                        this.oldOptionLabels[id] = oldText;
                    }
                }
            }
        },

        restoreInitialDropdown: function() {
            if (this.oldOptionLabels.length) {
                for (const id in this.oldOptionLabels) {
                    if (this.oldOptionLabels.hasOwnProperty(id)) {
                        const option = $(this.attributeSelect).find('option[value="' + id + '"]');
                        $(option).text(this.oldOptionLabels[id]);
                    }
                }
            }
        },

        /**
         * @param {Object} eventData
         */
        onAttributeSelect: function(eventData) {
            if (eventData.ftid !== this.ftid) {
                this.applyOption(eventData.selectedValue, false, this.attributeSelect);
            }
        },

        onGroupRemove: function(eventData) {
            if (eventData.attributeSelectFtid !== this.ftid) {
                return;
            }

            // Move system attributes to the first group
            const selectedSystemOptions = $(this.attributeSelect).find('option[locked="locked"]:selected');
            const optionsArray = $(selectedSystemOptions).map(function() {
                return this.value;
            }).get();

            if (optionsArray.length) {
                const select = eventData.firstGroup.find('[data-bound-input-widget="select2"]');
                for (let i = 0; i < optionsArray.length; i++) {
                    const value = optionsArray[i];
                    this.applyOption(value, true, select);
                }
                const message = _.__('oro.attribute.attributes_moved_to_default_group');
                mediator.execute('showFlashMessage', 'info', message);
            }
        },

        /**
         * @param {Integer} value
         * @param {Boolean} isSelected
         * @param {Object} select
         */
        applyOption: function(value, isSelected, select) {
            let option;
            if (isSelected) {
                option = $(select).find('option[value="' + value + '"]').not(':selected');
            } else {
                option = $(select).find('option[value="' + value + '"]:selected');
            }

            if (option.length) {
                // Need this timeout to deffer Change call because it causes some delay and it may be visible on UI
                setTimeout(function() {
                    $(option).prop('selected', isSelected).change();
                }, 1);
            }
        },

        addLabelsToAttributesFromOtherGroups: function() {
            const eventData = {attributeSelects: []};
            mediator.trigger('attribute-select:find-selected-attributes', eventData);

            const selectedOptions = [];
            const self = this;
            $(eventData.attributeSelects).each(function(key, value) {
                const groupLabel = value.groupLabel;
                const attributesSelect = value.attributesSelect;
                if ($(attributesSelect).data('ftid') === self.ftid) {
                    return;
                }

                $(attributesSelect).find('option:selected').each(function() {
                    const val = $(this).val();
                    selectedOptions[val] = groupLabel;
                });
            });

            this.prepareDropdown(selectedOptions);
        },

        /**
         * @param {Object} e
         */
        dispose: function(e) {
            mediator.off('attribute-select:selected', this.onAttributeSelect, this);
            mediator.off('attribute-group:remove', this.onGroupRemove, this);

            AttributeSelectComponent.__super__.dispose.call(this);
        }
    });

    return AttributeSelectComponent;
});
