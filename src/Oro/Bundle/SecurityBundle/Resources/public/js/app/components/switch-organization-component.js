define(function(require) {
    'use strict';

    var ChangeOrganizationComponent;
    var module = require('module');
    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var toolsRouting = require('oronavigation/js/tools/routing');
    var modalContentTemplate = require('tpl!orosecurity/templates/organization-modal-content.html');
    var BaseComponent = require('oroui/js/app/components/base/component');
    var pageStateChecker = require('oronavigation/js/app/services/page-state-checker');
    var Modal = require('oroui/js/modal');

    var defaults = {
        switchOrganizationRoute: 'oro_security_switch_organization',
        modalTitle: __('orosecurity.switch_organization_modal.title'),
        modalUnsavedDataText: __('orosecurity.switch_organization_modal.unsaved_data_text'),
        modalSavedDataText: __('orosecurity.switch_organization_modal.saved_data_text'),
        modalQuestion: __('orosecurity.switch_organization_modal.question')
    };

    ChangeOrganizationComponent = BaseComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ChangeOrganizationComponent() {
            ChangeOrganizationComponent.__super__.constructor.apply(this, arguments);
        },

        delegateListeners: function() {
            ChangeOrganizationComponent.__super__.delegateListeners.call(this);

            this.firstListenTo(mediator, 'openLink:before', this.beforeOpenLink);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var names = _.keys(defaults);

            _.extend(this, defaults, _.pick(module.config(), names), _.pick(options, names));
            this.matchingRouteRxp = toolsRouting.getRouteRegExp(this.switchOrganizationRoute);
        },

        /**
         * Create Modal for unsaved data
         */
        createDangerModal: function() {
            var modal = new Modal({
                className: 'modal oro-modal-danger',
                okButtonClass: 'btn btn-danger',
                okText: __('Yes'),
                title: this.modalTitle,
                content: modalContentTemplate({
                    translations: [this.modalUnsavedDataText, this.modalQuestion]
                })
            });
            modal.on('ok', this.switchOrganization.bind(this));
            modal.on('cancel', this.stayInCurrentOrganization.bind(this));
            // TODO: should check correct name of event
            this.once('organization:do-switch', function() {
                modal.close();
            });
            modal.open();
        },

        /**
         * Create Modal for saved data
         */
        createWarningModal: function() {
            var modal = new Modal({
                className: 'modal modal-primary',
                okText: __('Yes'),
                title: this.modalTitle,
                content: modalContentTemplate({
                    translations: [this.modalSavedDataText, this.modalQuestion]
                })
            });
            modal.on('ok', this.switchOrganization.bind(this));
            modal.on('cancel', this.stayInCurrentOrganization.bind(this));
            // TODO: should check correct name of event
            this.once('all-tabs-are-saved', function() {
                modal.close();
            });
            modal.open();
        },

        /**
         * Check if clicked link match to switch organization
         * @param event
         */
        beforeOpenLink: function(event) {
            var url = event.target.href;

            if (this.matchingRouteRxp === void 0 ||
                url.match(this.matchingRouteRxp) === null) {
                return;
            }

            // event.prevented = true;

            this.lastLink = url;

            // this.createDangerModal();
        },

        /**
         * Callback for leave current organization
         */
        switchOrganization: function() {
            pageStateChecker.ignoreChanges();
            mediator.execute('showLoading');
            mediator.execute('redirectTo', {url: this.lastLink}, {redirect: true, fullRedirect: true});
        },

        /**
         * Callback for stay at current organization
         */
        stayInCurrentOrganization: function() {}
    });

    return ChangeOrganizationComponent;
});

