define(function(require) {
    'use strict';

    const _ = require('underscore');
    const BaseCollectionView = require('oroui/js/app/views/base/collection-view');
    const CapabilityItemView = require('orouser/js/views/role/capability-item-view');

    let config = require('module-config').default(module.id);
    config = _.extend({
        selectAllClassName: 'btn btn-link btn-sm'
    }, config);

    /**
     * @export orouser/js/views/role-view
     */
    const CapabilityGroupView = BaseCollectionView.extend({
        animationDuration: 0,

        className: 'role-capability',

        template: require('tpl-loader!orouser/templates/role/capability-group.html'),

        listSelector: '[data-name="capability-items"]',

        fallbackSelector: '[data-name="capability-empty-items"]',

        itemView: CapabilityItemView,

        listen: {
            'change collection': 'onChange'
        },

        events: {
            'click [data-name="capabilities-select-all"]': 'onSelectAll'
        },

        /**
         * @inheritdoc
         */
        constructor: function CapabilityGroupView(options) {
            CapabilityGroupView.__super__.constructor.call(this, options);
        },

        initialize: function(options) {
            this.model = options.model;
            this.collection = options.model.get('items');
            CapabilityGroupView.__super__.initialize.call(this, options);
        },

        getTemplateData: function() {
            const templateData = CapabilityGroupView.__super__.getTemplateData.call(this);
            _.defaults(templateData, this.model.toJSON());
            templateData.allSelected = this.isAllSelected();
            templateData.selectAllClassName = config.selectAllClassName;
            return templateData;
        },

        onSelectAll: function(e) {
            e.preventDefault();
            this.collection.each(function(model) {
                model.set('access_level', model.get('selected_access_level'));
            });
        },

        onChange: function() {
            this.$('[data-name="capabilities-select-all"]').attr('disabled', this.isAllSelected());
        },

        isAllSelected: function() {
            return !this.collection.find(function(model) {
                return model.get('access_level') !== model.get('selected_access_level');
            });
        }
    });

    return CapabilityGroupView;
});
