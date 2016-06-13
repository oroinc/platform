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
        template: require('tpl!orouser/templates/capability-group.html'),
        listSelector: '.capability-items',
        itemView: CapabilityItemView,
        listen: {
            'change collection': 'onChange'
        },
        events: {
            'click .select-all-capabilities': 'onSelectAll'
        },
        initialize: function(options) {
            this.model = options.model;
            this.collection = options.model.get('items');
            CapabilityGroupView.__super__.initialize.apply(this, arguments);
        },

        getTemplateData: function() {
            var templateData = CapabilityGroupView.__super__.getTemplateData.apply(this, arguments);
            _.defaults(templateData, this.model.toJSON());
            templateData.allSelected = this.collection.where({'accessLevel': 0}).length === 0;
            return templateData;
        },

        onSelectAll: function(e) {
            e.preventDefault();
            this.collection.each(function(model) {
                model.set('accessLevel', 5);
            });
        },

        onChange: function() {
            this.$('.select-all-capabilities').toggleClass('disabled', !this.collection.findWhere({'accessLevel': 0}));
        }

    });

    return CapabilityGroupView;
});
