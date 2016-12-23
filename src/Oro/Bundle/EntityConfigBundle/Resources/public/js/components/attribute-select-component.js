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
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.attributeSelect = options._sourceElement;
            this.ftid = $(this.attributeSelect).data('ftid');
            var self = this;

            AttributeSelectComponent.__super__.initialize.call(this, options);

            //Modify dropdown - show already selected attributes (from other groups) in braces
            $(this.attributeSelect).on('select2-opening', function(e) {
                self.selectedAttributesOtherSelects = [];
                var data = {'sourceFtid': self.ftid, 'selectedOptions': []};
                mediator.trigger('attribute-select:find-selected-attributes',  data);
                self.prepareDropdown(data.selectedOptions);
            });

            //Restore dropdown with initial option labels
            $(this.attributeSelect).on('select2-close', function(e) {
                self.restoreInitialDropdown();
            });

            $(this.attributeSelect).on('selected', function(e) {
                //Modify selected tag to show initial value (without braces)
                if (e.val in self.oldOptionLabels) {
                    var tag =  $(self.attributeSelect).parent().find('.select2-choices li div').last();
                    $(tag).html(self.oldOptionLabels[e.val]);
                }
                var eventData = {
                    'ftid': self.ftid,
                    'selectedValue': e.val
                };
                mediator.trigger('attribute-select:selected', eventData);
            });

            mediator.on('attribute-select:selected', this.onAttributeSelect, this);

            if (options.configs.isDefault) {
                mediator.on('attribute-select:group-remove', this.onGroupRemove, this);
            }
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
                this.applyOption(eventData.selectedValue, false);
            }
        },

        /**
         * @param {Array} optionsArray
         */
        onGroupRemove: function(optionsArray) {
            for (var i = 0; i < optionsArray.length; i++) {
                var value = optionsArray[i];
                this.applyOption(value, true);
            }
            var message = _.__('oro.attribute.attributes_moved_to_default_group');
            mediator.execute('showFlashMessage', 'info', message);
        },

        /**
         * @param {Integer} value
         * @param {Boolean} select
         */
        applyOption: function(value, select) {
            var option;
            if (select) {
                option = $(this.attributeSelect).find('option[value="' + value + '"]').not(':selected');
            } else {
                option = $(this.attributeSelect).find('option[value="' + value + '"]:selected');
            }

            if (option.length) {
                //Need this timeout to deffer Change call because it causes some delay and it may be visible on UI
                setTimeout(function() {
                    $(option).prop('selected', select).change();
                }, 1);
            }
        },

        /**
         * @param {Object} e
         */
        dispose: function(e) {
            mediator.off('attribute-select:selected', this.onAttributeSelect, this);
            if (this.options.configs.isDefault) {
                mediator.off('attribute-select:group-remove', this.onGroupRemove, this);
            }
            //Find all selected system attributes and move it default group
            var selectedSystemOptions = $(this.attributeSelect).find('option[locked="locked"]:selected');
            var optionsArray = $(selectedSystemOptions).map(function() {
                return this.value;
            }).get();

            if (optionsArray.length) {
                mediator.trigger('attribute-select:group-remove', optionsArray);
            }
        }
    });

    return AttributeSelectComponent;
});
