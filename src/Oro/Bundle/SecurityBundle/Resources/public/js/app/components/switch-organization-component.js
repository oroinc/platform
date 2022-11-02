define(function(require, exports, module) {
    'use strict';

    const config = require('module-config').default(module.id);
    const $ = require('jquery');
    const _ = require('underscore');
    const __ = require('orotranslation/js/translator');
    const mediator = require('oroui/js/mediator');
    const routing = require('routing');
    const toolsRouting = require('oronavigation/js/tools/routing');
    const modalContentTemplate = require('tpl-loader!orosecurity/templates/organization-modal-content.html');
    const BaseComponent = require('oroui/js/app/components/base/component');
    const Modal = require('oroui/js/modal');
    const interWindowMediator = require('oroui/js/app/services/inter-window-mediator');
    const pageStateChecker = require('oronavigation/js/app/services/page-state-checker');
    const HighlighterFavicon = require('oroui/js/tools/highlighter/highlighter-favicon');
    const HighlighterTitle = require('oroui/js/tools/highlighter/highlighter-title');

    const STATES = {
        INITIAL: 0,
        LOCAL_CHANGES: 1,
        REMOTE_CHANGES: 2,
        NO_CHANGES: 3,
        CANCELED: 4,
        DO_CHANGE: 5
    };

    const defaults = {
        switchOrganizationRoute: 'oro_security_switch_organization',
        modalTitle: __('orosecurity.switch_organization_modal.title'),
        modalRemoteChangesText: __('orosecurity.switch_organization_modal.unsaved_data_text'),
        modalLocalChangesText: __('oro.ui.leave_page_with_unsaved_data_confirm'),
        modalNoChangesText: __('orosecurity.switch_organization_modal.saved_data_text'),
        modalQuestion: __('orosecurity.switch_organization_modal.question'),
        pollingIntervalTimeout: 250,
        removeWindowDelay: 100
    };

    const ChangeOrganizationComponent = BaseComponent.extend({
        state: null,

        pollingIntervalId: null,

        highlighter: null,

        modal: null,

        /**
         * @inheritdoc
         */
        constructor: function ChangeOrganizationComponent(options) {
            ChangeOrganizationComponent.__super__.constructor.call(this, options);
        },

        delegateListeners: function() {
            ChangeOrganizationComponent.__super__.delegateListeners.call(this);

            this.firstListenTo(mediator, 'openLink:before', this.beforeOpenLink);
            this.listenTo(interWindowMediator, 'organization:before-change', this.onRemoteBeforeOrganizationChange);
            this.listenTo(interWindowMediator, 'organization:do-change', this.onRemoteDoOrganizationChange);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            const names = _.keys(defaults);
            _.extend(this, defaults, _.pick(config, names),
                _.pick(options, names), _.pick(options, 'currentOrganizationId'));

            ChangeOrganizationComponent.__super__.initialize.call(this, options);

            this.matchingRouteRxp = toolsRouting.getRouteRegExp(this.switchOrganizationRoute, 'i');
            this.setState(STATES.INITIAL);
        },

        onRemoteBeforeOrganizationChange: function() {
            if (this.state !== STATES.INITIAL) {
                this.cancelOrganizationChange();
            }

            if (this.mightEventualDataLoss()) {
                interWindowMediator.trigger('organization:prevent-change');
                this.highlight();
            } else {
                this.unhighlight();
            }
        },

        onRemoteDoOrganizationChange: function(newOrganizationId) {
            pageStateChecker.ignoreChanges();

            const redirectTo = function() {
                const pathDef = {url: routing.generate('oro_default')};
                const options = {redirect: true, fullRedirect: true};
                mediator.execute('showLoading');
                mediator.execute('redirectTo', pathDef, options);
            };

            if (document.hidden) {
                $(document).off('visibilitychange.' + this.cid);
                if (this.currentOrganizationId !== newOrganizationId) {
                    $(document).one('visibilitychange.' + this.cid, redirectTo);
                } else {
                    // returned back to the original organization while the tab was hidden
                    pageStateChecker.notIgnoreChanges();
                }
            } else {
                redirectTo();
            }
        },

        setState: function(state) {
            if (state === this.state) {
                return;
            }

            if (this.modal) {
                this.modal.close();
                this.modal = null;
            }

            let modalOptions;

            switch (state) {
                case STATES.LOCAL_CHANGES:
                    modalOptions = {
                        className: 'modal oro-modal-danger',
                        okButtonClass: 'btn btn-danger',
                        content: modalContentTemplate({
                            paragraphs: [this.modalLocalChangesText]
                        }),
                        attributes: {
                            role: 'alertdialog'
                        }
                    };

                    break;

                case STATES.REMOTE_CHANGES:
                    modalOptions = {
                        className: 'modal oro-modal-danger',
                        okButtonClass: 'btn btn-danger',
                        content: modalContentTemplate({
                            paragraphs: [this.modalRemoteChangesText, this.modalQuestion]
                        }),
                        attributes: {
                            role: 'alertdialog'
                        }
                    };

                    break;

                case STATES.NO_CHANGES:
                    modalOptions = {
                        className: 'modal modal-primary',
                        content: modalContentTemplate({
                            paragraphs: [this.modalNoChangesText, this.modalQuestion]
                        })
                    };

                    break;
            }

            if (modalOptions) {
                this.modal = new Modal(_.extend({
                    okText: __('Yes'),
                    title: this.modalTitle
                }, modalOptions));

                this.modal.on('ok', this.switchOrganization.bind(this));
                this.modal.on('cancel', this.cancelOrganizationChange.bind(this));
                this.modal.open();
            }

            this.state = state;
        },

        /**
         * Check if clicked link match to switch organization
         * @param event
         */
        beforeOpenLink: function(event) {
            const url = event.target.href;
            let matches;

            if (this.matchingRouteRxp === void 0 ||
                (matches = url.match(this.matchingRouteRxp)) === null) {
                return;
            }

            const routeVariables = routing.getRoute(this.switchOrganizationRoute).tokens
                .filter(function(token) {
                    return token[0] === 'variable';
                })
                .reverse();
            const idIndex = _.findIndex(routeVariables, function(token) {
                return token[3] === 'id';
            });

            event.prevented = true;

            this.organizationUrl = url;
            this.organizationId = Number(matches[idIndex + 1]);

            this.setState(STATES.INITIAL);
            this.beforeOrganizationChange();
            this.pollingIntervalId = setInterval(this.beforeOrganizationChange.bind(this), this.pollingIntervalTimeout);
        },

        beforeOrganizationChange: function() {
            if (this.mightEventualDataLoss()) {
                this.highlight();
            } else {
                this.unhighlight();
            }

            const timeoutId = _.delay(function() {
                interWindowMediator.off('organization:prevent-change', onRemoteChanges);
                this.onNoRemoteChanges();
            }.bind(this), this.removeWindowDelay);

            const onRemoteChanges = this.onRemoteChanges.bind(this, timeoutId);

            interWindowMediator.once('organization:prevent-change', onRemoteChanges);

            interWindowMediator.trigger('organization:before-change');
        },

        onRemoteChanges: function(timeoutId) {
            clearInterval(timeoutId);

            if (this.state !== STATES.CANCELED) {
                this.setState(STATES.REMOTE_CHANGES);
            }
        },

        onNoRemoteChanges: function() {
            if (this.state === STATES.CANCELED) {
                return;
            } else if (this.mightEventualDataLoss()) {
                this.setState(STATES.LOCAL_CHANGES);
            } else if (this.state === STATES.INITIAL) {
                this.switchOrganization();
            } else {
                this.setState(STATES.NO_CHANGES);
            }
        },

        /**
         * Callback for stay at current organization
         */
        cancelOrganizationChange: function() {
            this.setState(STATES.CANCELED);
            this.stopBeforeChangePolling();
        },

        stopBeforeChangePolling: function() {
            if (this.pollingIntervalId) {
                clearInterval(this.pollingIntervalId);
                this.pollingIntervalId = null;
            }
        },

        /**
         * Callback for leave current organization
         */
        switchOrganization: function() {
            this.setState(STATES.DO_CHANGE);
            this.stopBeforeChangePolling();
            pageStateChecker.ignoreChanges();
            mediator.execute('showLoading');

            const headerId = mediator.execute('retrieveOption', 'headerId');
            const newOrganizationId = this.organizationId;

            $.get({
                url: this.organizationUrl,
                success: function(data) {
                    const pathDesc = {url: data.location};
                    const options = _.omit(data, 'location');
                    interWindowMediator.trigger('organization:do-change', newOrganizationId);
                    mediator.execute('redirectTo', pathDesc, options);
                },
                headers: _.object([headerId], [true])
            }).fail(function() {
                pageStateChecker.notIgnoreChanges();
                mediator.execute('hideLoading');
            });
        },

        initHighlighter: function() {
            if (/(Chrome|Firefox|Opera)\/\d+\.\d+/.test(navigator.userAgent) &&
                !/(Edge|Trident)\/\d+\.\d+/.test(navigator.userAgent)) {
                this.highlighter = new HighlighterFavicon();
            } else {
                this.highlighter = new HighlighterTitle();
            }
        },

        mightEventualDataLoss: function() {
            return pageStateChecker.isStateChanged() && !pageStateChecker.hasChangesIgnored();
        },

        highlight: function() {
            if (this.disposed) {
                return;
            }

            if (!this.highlighter) {
                this.initHighlighter();
            }

            if (this.highlighterTimeoutId) {
                clearTimeout(this.highlighterTimeoutId);
                delete this.highlighterTimeoutId;
            }

            this.highlighter.highlight();
            this.highlighterTimeoutId = _.delay(this.unhighlight.bind(this), this.pollingIntervalTimeout);
        },

        unhighlight: function() {
            if (this.disposed || !this.highlighter) {
                return;
            }

            if (this.highlighterTimeoutId) {
                clearTimeout(this.highlighterTimeoutId);
                delete this.highlighterTimeoutId;
            }

            this.highlighter.unhighlight();
        },

        dispose: function() {
            if (this.disposed) {
                return false;
            }

            $(document).off('.' + this.cid);

            ChangeOrganizationComponent.__super__.dispose.call(this);
        }
    });

    return ChangeOrganizationComponent;
});
