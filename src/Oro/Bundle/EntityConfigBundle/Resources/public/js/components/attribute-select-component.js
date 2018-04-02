define(function(require) {
    'use strict';

    var AttributeSelectComponent;
    var $ = require('jquery');
    var mediator = require('oroui/js/mediator');
    var _ = require('underscore');

    var Select2AutocompleteComponent = require('oro/select2-autocomplete-component');

    AttributeSelectComponent = Select2AutocompleteComponent.extend({
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
         * @inheritDoc
         */
        constructor: function AttributeSelectComponent() {
            AttributeSelectComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.attributeSelect = options._sourceElement;
            this.ftid = $(this.attributeSelect).data('ftid');
            var self = this;

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
                    var tag = $(self.attributeSelect).parent().find('.select2-choices li div').last();
                    $(tag).html(self.oldOptionLabels[e.val]);
                }
                var eventData = {
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
                for (var id in selectedOptions) {
                    if (selectedOptions.hasOwnProperty(id)) {
                        var option = $(this.attributeSelect).find('option[value="' + id + '"]');
                        var oldText = $(option).text();
                        var moveFrom = _.__('oro.attribute.move_from');
                        var groupName = selectedOptions[id] ? selectedOptions[id] : _.__('oro.attribute.noname');
                        var newText = oldText + '(' + moveFrom + ' ' + groupName + ')';
                        $(option).text(newText);
                        this.oldOptionLabels[id] = oldText;
                    }
                }
            }
        },

        restoreInitialDropdown: function() {
            if (this.oldOptionLabels.length) {
                for (var id in this.oldOptionLabels) {
                    if (this.oldOptionLabels.hasOwnProperty(id)) {
                        var option = $(this.attributeSelect).find('option[value="' + id + '"]');
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
            var selectedSystemOptions = $(this.attributeSelect).find('option[locked="locked"]:selected');
            var optionsArray = $(selectedSystemOptions).map(function() {
                return this.value;
            }).get();

            if (optionsArray.length) {
                var select = eventData.firstGroup.find('[data-bound-input-widget="select2"]');
                for (var i = 0; i < optionsArray.length; i++) {
                    var value = optionsArray[i];
                    this.applyOption(value, true, select);
                }
                var message = _.__('oro.attribute.attributes_moved_to_default_group');
                mediator.execute('showFlashMessage', 'info', message);
            }
        },

        /**
         * @param {Integer} value
         * @param {Boolean} isSelected
         * @param {Object} select
         */
        applyOption: function(value, isSelected, select) {
            var option;
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
            var eventData = {attributeSelects: []};
            mediator.trigger('attribute-select:find-selected-attributes', eventData);

            var selectedOptions = [];
            var self = this;
            $(eventData.attributeSelects).each(function(key, value) {
                var groupLabel = value.groupLabel;
                var attributesSelect = value.attributesSelect;
                if ($(attributesSelect).data('ftid') === self.ftid) {
                    return;
                }

                $(attributesSelect).find('option:selected').each(function() {
                    var val = $(this).val();
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
