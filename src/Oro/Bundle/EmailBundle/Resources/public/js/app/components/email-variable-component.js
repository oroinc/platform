import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import EmailVariableView from 'oroemail/js/app/views/email-variable-view';
import EmailVariableModel from 'oroemail/js/app/models/email-variable-model';
import DeleteConfirmation from 'oroui/js/delete-confirmation';
import BaseComponent from 'oroui/js/app/components/base/component';

const EmailVariableComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function EmailVariableComponent(options) {
        EmailVariableComponent.__super__.constructor.call(this, options);
    },

    /**
     * @constructor
     * @param {Object} options
     */
    initialize: function(options) {
        let attributes;

        _.defaults(options, {model: {}, view: {}});

        // create model
        attributes = options.model.attributes;
        attributes = attributes ? JSON.parse(attributes) : {};
        this.model = new EmailVariableModel(attributes);
        this.model.setEntity(options.model.entityName, options.model.entityLabel);

        // create view
        options.view.el = options._sourceElement;
        options.view.model = this.model;
        this.view = new EmailVariableView(options.view);

        // bind entity change handler
        this.entityChoice = $(options.entityChoice);
        this.entityChoice.on('change.' + this.cid, this.onEntityChange.bind(this));

        this.view.render();
    },

    onEntityChange: function(e) {
        const view = this.view;
        const $el = $(e.currentTarget);
        const entityName = $el.val();
        const entityLabel = $el.find(':selected').data('label');

        if (!this.view.isEmpty()) {
            const confirm = new DeleteConfirmation({
                title: __('Change Entity Confirmation'),
                okText: __('Yes'),
                content: __('oro.email.emailtemplate.change_entity_confirmation')
            });

            confirm.on('ok', function() {
                view.clear();
            });

            confirm.open();
        }

        this.model.setEntity(entityName, entityLabel);
    },

    dispose: function() {
        if (this.disposed) {
            return;
        }

        this.entityChoice.off('.' + this.cid);
        delete this.entityChoice;

        EmailVariableComponent.__super__.dispose.call(this);
    }
});

export default EmailVariableComponent;
