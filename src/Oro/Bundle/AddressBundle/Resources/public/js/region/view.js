define(function(require) {
    'use strict';

    var AddressRegionView;
    var _ = require('underscore');
    var $ = require('jquery');
    var RegionCollection = require('oroaddress/js/region/collection');
    var Backbone = require('backbone');
    var module = require('module');
    var config = _.defaults(module.config(), {
        switchState: false
    });
    require('jquery.select2');
    require('jquery.validate');
    require('oroui/js/input-widget-manager');

    /**
     * @export  oro/region/view
     * @class   oro.region.View
     * @extends Backbone.View
     */
    AddressRegionView = Backbone.View.extend({
        events: {
            change: 'selectionChanged'
        },

        switchState: config.switchState,

        /**
         * @inheritDoc
         */
        constructor: function AddressRegionView() {
            AddressRegionView.__super__.constructor.apply(this, arguments);
        },

        /**
         * Constructor
         *
         * @param options {Object}
         */
        initialize: function(options) {
            this.target = $(options.target);
            this.$simpleEl = this.switchState !== 'disable' ? $(options.simpleEl) : null;

            if (this.$simpleEl) {
                this.target.after(this.$simpleEl);
                this.$simpleEl.attr('type', 'text');
            }

            this.showSelect = options.showSelect;
            this.regionRequired = options.regionRequired;

            this.template = _.template($('#region-chooser-template').html());

            this.displaySelect2(this.showSelect);
            this.target.on('input-widget:init', _.bind(function() {
                this.displaySelect2(this.showSelect);
            }, this));

            if (options.collectionRoute && !this.collection) {
                this.collection = new RegionCollection([], {
                    route: options.collectionRoute
                });
            }

            this.listenTo(this.collection, 'reset', this.render);
        },

        /**
         * Show/hide select 2 element
         *
         * @param {Boolean} display
         */
        displaySelect2: function(display) {
            if (display) {
                if (this.regionRequired) {
                    this.addRequiredFlag();
                }
                this.switchInputWidget(display);
            } else {
                this.switchInputWidget(display);
                if (this.regionRequired) {
                    this.removeRequiredFlag();
                }
                if (this.target.closest('form').data('validator')) {
                    this.target.validate().hideElementErrors(this.target);
                }
            }
        },

        addRequiredFlag: function() {
            var label = this.getInputLabel(this.target);
            if (!label.hasClass('required')) {
                label
                    .addClass('required')
                    .find('em').html('*');
            }
        },

        removeRequiredFlag: function() {
            var label = this.getInputLabel(this.target);
            if (label.hasClass('required')) {
                label
                    .removeClass('required')
                    .find('em').html('&nbsp;');
            }
        },

        getInputLabel: function(el) {
            var label;
            var input = _.result(el.data('select2'), 'focusser') || el;
            var id = input.attr('id');

            if (id) {
                label = $('label[for="' + id + '"]');
            }

            return label && label.length ? label : el.parent().parent().find('label');
        },

        /**
         * Trigger change event
         */
        sync: function() {
            if (this.target.val() === '' && this.$el.val() !== '') {
                this.$el.trigger('change');
            }
        },

        /**
         * onChange event listener
         *
         * @param e {Object}
         */
        selectionChanged: function(e) {
            this.$el.trigger('value:changing');
            if ($(e.currentTarget).val()) {
                var countryId = $(e.currentTarget).val();
                this.collection.setCountryId(countryId);
                this.collection.fetch({reset: true});
            } else {
                this.collection.reset([]);
            }
        },

        render: function() {
            if (this.collection.models.length > 0) {
                this.target.show();
                this.displaySelect2(true);
                this.target.find('option[value!=""]').remove();
                this.target.append(this.template({regions: this.collection.models}));
                this.target.val(this.target.data('selected-data') || '').trigger('change');

                if (this.$simpleEl) {
                    this.$simpleEl.hide().val('');
                }
            } else {
                this.target.hide();
                this.target.inputWidget('val', '');
                this.displaySelect2(false);

                if (this.$simpleEl) {
                    this.$simpleEl.show();
                }
            }
            this.$el.trigger('value:changed');
        },

        switchInputWidget: function(display) {
            switch (this.switchState) {
                case 'disable':
                    this.target.inputWidget('disable', !display);
                    break;
                default: {
                    this.target.inputWidget(display ? 'show' : 'hide');
                }
            }
        }
    });

    return AddressRegionView;
});
