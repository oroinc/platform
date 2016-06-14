define(function(require) {
    'use strict';

    var CapabilityGroupView;
    var _ = require('underscore');
    var BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    var CapabilityItemView = require('orouser/js/views/capability-item-view');

    /**
     * @export orouser/js/views/role-view
     */
    CapabilityGroupView = BaseCollectionView.extend({
        animationDuration: 0,
        className: 'role-capability',
        template: require('tpl!orouser/templates/capability-group.html'),
        listSelector: '[data-name="capability-items"]',
        itemView: CapabilityItemView,
        listen: {
            'change collection': 'onChange'
        },
        events: {
            'click [data-name="capabilities-select-all"]': 'onSelectAll'
        },
        initialize: function(options) {
            this.model = options.model;
            this.collection = options.model.get('items');
            CapabilityGroupView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var templateData = CapabilityGroupView.__super__.getTemplateData.apply(this, arguments);
            _.defaults(templateData, this.model.toJSON());
            templateData.allSelected = this.isAllSelected();
            return templateData;
        },

        onSelectAll: function(e) {
            e.preventDefault();
            this.collection.each(function(model) {
                model.set('value', model.get('selected_value'));
            });
        },

        onChange: function() {
            this.$('[data-name="capabilities-select-all"]')
                .toggleClass('disabled', this.isAllSelected());
        },

        isAllSelected: function() {
            return !this.collection.find(function(model) {
                return model.get('value') !== model.get('selected_value');
            });
        }
    });

    return CapabilityGroupView;
});
