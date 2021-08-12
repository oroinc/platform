define([
    'jquery',
    'underscore',
    'oroui/js/app/models/base/model'
], function($, _, BaseModel) {
    'use strict';

    const ActivityListModel = BaseModel.extend({
        defaults: {
            id: '',

            owner: '',
            owner_id: '',

            editor: '',
            editor_id: '',

            organization: '',
            verb: '',
            subject: '',
            data: '',
            configuration: '',
            commentCount: 0,

            activityEntityClass: '',
            activityEntityId: '',

            createdAt: '',
            updatedAt: '',

            is_loaded: false,
            is_head: false,
            contentHTML: '',

            editable: true,
            removable: true,
            commentable: false,

            targetEntityData: {},

            routes: {}
        },

        /**
         * @inheritdoc
         */
        constructor: function ActivityListModel(...args) {
            ActivityListModel.__super__.constructor.apply(this, args);
        },

        /**
         * @inheritdoc
         */
        initialize: function(...args) {
            this.once('change:contentHTML', function() {
                this.set('is_loaded', true);
            });
            ActivityListModel.__super__.initialize.apply(this, args);
        },

        getRelatedActivityClass: function() {
            return this.get('relatedActivityClass').replace(/\\/g, '_');
        },

        /**
         * Compares current model to attributes. Returns true if models are same
         *
         * @param model {Object|ActivityListModel} attributes or model to compare
         */
        isSameActivity: function(model) {
            const attrsToCompare = model instanceof ActivityListModel ? model.toJSON() : model;

            if (attrsToCompare.id === this.get('id')) {
                return true;
            }

            if (attrsToCompare.relatedActivityClass === this.get('relatedActivityClass')) {
                if (attrsToCompare.relatedActivityId === this.get('relatedActivityId')) {
                    return true;
                }
                // @TODO: move to descendant
                if (attrsToCompare.relatedActivityClass === 'Oro\\Bundle\\EmailBundle\\Entity\\Email') {
                    // if tread is same
                    if (attrsToCompare.data.treadId !== null &&
                        attrsToCompare.data.treadId === this.get('data').treadId) {
                        return true;
                    }
                    // if compared model is not in tread and if tread was just created (it contains replayedEmailId)
                    // models are same
                    if (attrsToCompare.data.treadId === null &&
                        this.get('data').replayedEmailId === attrsToCompare.relatedActivityId) {
                        return true;
                    }
                }
            }
            return false;
        },

        loadContentHTML: function(url) {
            const options = {
                url: url,
                type: 'get',
                dataType: 'html',
                data: {
                    _widgetContainer: 'dialog',
                    targetActivityClass: this.get('targetEntityData').class,
                    targetActivityId: this.get('targetEntityData').id
                }
            };

            this.set('isContentLoading', true);
            return $.ajax(options)
                .done(data => {
                    this.set({
                        is_loaded: true,
                        contentHTML: data,
                        isContentLoading: false
                    });
                })
                .fail(response => {
                    const attrs = {isContentLoading: false};
                    if (response.status === 403) {
                        attrs.is_loaded = true;
                    }
                    this.set(attrs);
                });
        }
    });

    return ActivityListModel;
});
