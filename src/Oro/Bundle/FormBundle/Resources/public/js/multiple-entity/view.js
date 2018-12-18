define(function(require) {
    'use strict';

    var EntityView;
    var _ = require('underscore');
    var Backbone = require('backbone');
    var DialogWidget = require('oro/dialog-widget');

    /**
     * @export  oroform/js/multiple-entity/view
     * @class   oroform.MultipleEntity.View
     * @extends Backbone.View
     */
    EntityView = Backbone.View.extend({
        attributes: {
            'class': 'list-group-item'
        },

        events: {
            'click .remove-btn': 'removeElement',
            'change .default-selector': 'defaultSelected'
        },

        options: {
            name: null,
            hasDefault: false,
            defaultRequired: false,
            model: null,
            template: null
        },

        /**
         * @inheritDoc
         */
        constructor: function EntityView() {
            EntityView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            this.options = _.defaults(options || {}, this.options);
            this.template = _.template(this.options.template);
            this.listenTo(this.model, 'destroy', this.remove);
            if (this.options.defaultRequired) {
                this.listenTo(this.model, 'change:isDefault', this.toggleDefault);
            }
        },

        /**
         * Display information about selected entity.
         *
         * @param {jQuery.Event} e
         */
        viewDetails: function(e) {
            e.stopImmediatePropagation();
            e.preventDefault();
            var widget = new DialogWidget({
                url: this.options.model.get('link'),
                title: this.options.model.get('label'),
                dialogOptions: {
                    allowMinimize: true,
                    width: 675,
                    autoResize: true
                }
            });
            widget.render();
        },

        removeElement: function() {
            this.trigger('removal', this.model);
            this.model.set('id', null);
            this.model.destroy();
        },

        defaultSelected: function(e) {
            this.options.model.set('isDefault', e.target.checked);
        },

        toggleDefault: function() {
            if (this.options.defaultRequired) {
                this.$el.find('.remove-btn')[0].disabled = this.model.get('isDefault');
            }
        },

        render: function() {
            var data = this.model.toJSON();
            data.hasDefault = this.options.hasDefault;
            data.name = this.options.name;
            this.$el.append(this.template(data));
            this.$el.find('a.entity-info').click(_.bind(this.viewDetails, this));
            this.toggleDefault();
            return this;
        }
    });

    return EntityView;
});
