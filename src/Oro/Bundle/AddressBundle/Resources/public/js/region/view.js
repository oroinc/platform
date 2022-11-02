define(function(require, exports, module) {
    'use strict';

    const _ = require('underscore');
    const $ = require('jquery');
    const RegionCollection = require('oroaddress/js/region/collection');
    const Backbone = require('backbone');
    let config = require('module-config').default(module.id);
    config = _.defaults({}, config, {
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
    const AddressRegionView = Backbone.View.extend({
        events: {
            change: 'selectionChanged',
            redraw: 'redraw'
        },

        switchState: config.switchState,

        /**
         * @inheritdoc
         */
        constructor: function AddressRegionView(options) {
            AddressRegionView.__super__.constructor.call(this, options);
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
            this.target.on('input-widget:init', () => {
                this.displaySelect2(this.showSelect);
            });
            this.target.on('input-widget:refresh', () => {
                const toShow = (this.collection && this.collection.models.length > 0) || this.target.val();

                this.displaySelect2(toShow);
            });

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
            }
        },

        addRequiredFlag: function() {
            const label = this.getInputLabel(this.target);
            if (!label.hasClass('required')) {
                label
                    .addClass('required')
                    .find('em').html('*');
            }
        },

        removeRequiredFlag: function() {
            const label = this.getInputLabel(this.target);
            if (label.hasClass('required')) {
                label
                    .removeClass('required')
                    .find('em').html('&nbsp;');
            }
        },

        getInputLabel: function(el) {
            let label;
            const input = _.result(el.data('select2'), 'focusser') || el;
            const id = input.attr('id');

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
         */
        selectionChanged: function() {
            this.$el.trigger('value:changing');
            const validator = this.target.closest('form').data('validator');
            if (validator) {
                validator.hideElementErrors(this.target[0]);
            }
            this.redraw();
            this.$el.trigger('value:changed');
        },

        redraw: function() {
            if (this.$simpleEl) {
                this.$simpleEl.hide();
            }
            const countryId = this.$el.val();
            if (countryId) {
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
                const value = this.target.data('selected-data') || '';
                this.target.select2('val', value);

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
