define(function(require) {
    'use strict';

    var EmailTemplateView;
    var $ = require('jquery');
    var _ = require('underscore');
    var BaseView = require('oroui/js/app/views/base/view');
    var EmailTemplateCollection = require('oroemail/js/app/models/email-template-collection');

    /**
     * @export oroemail/js/app/views/email-template-view
     */
    EmailTemplateView = BaseView.extend({
        optionNames: BaseView.prototype.optionNames.concat(['collectionOptions', 'targetSelector', 'target']),

        events: {
            change: 'selectionChanged'
        },

        /**
         * @inheritDoc
         */
        constructor: function EmailTemplateView() {
            EmailTemplateView.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function() {
            this.template = _.template($('#emailtemplate-chooser-template').html());

            if (this.collectionOptions) {
                this._initCollection(this.collectionOptions);
            }

            if (this.targetSelector) {
                this.target = $(this.targetSelector);
            }

            this.listenTo(this.collection, 'reset', this.render);

            if (!$(this.target).val()) {
                this.selectionChanged();
            }
        },

        _initCollection: function(options) {
            this.collection = new EmailTemplateCollection(null, options);
        },

        /**
         * onChange event listener
         */
        selectionChanged: function() {
            var entityId = this.$el.val();
            this.collection.setEntityId(entityId.split('\\').join('_'));
            if (entityId) {
                this.collection.fetch({reset: true});
            } else {
                this.collection.reset();
            }
        },

        render: function() {
            $(this.target).val('').trigger('change');
            $(this.target).find('option[value!=""]').remove();
            if (this.collection.models.length > 0) {
                $(this.target).append(this.template({entities: this.collection.models}));
            }
        }
    });

    return EmailTemplateView;
});
