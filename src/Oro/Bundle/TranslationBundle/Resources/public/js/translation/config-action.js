/* jshint devel:true */
/* global define */
define(['backbone', 'underscore', 'routing', 'oro/translator', 'oro/navigation', 'oro/messenger'],
function (Backbone, _, routing, __, Navigation, messenger) {
    var $ = Backbone.$;

    /**
     * @export  oro/translation/translations/config-action
     * @extends Backbone.View
     */
    return Backbone.View.extend({
        /**
         * Status constants could be overridden if passed to constructor
         * {@see Oro\Bundle\TranslationBundle\Translation\TranslationStatusInterface }
         *
         * @property {}
         */
        options: {
            STATUS_NEW: 1,
            STATUS_DOWNLOADED: 2,
            STATUS_ENABLED: 3
        },

        route: 'oro_translation_download',

        buttonsSelector: '.available-translation-widget-container .btn',

        buttonTemplate: _.template('<button class="btn btn-mini" data-lang="<%= code %>" data-action="<%= action %>"><%= label %></button>'),

        /**
         * Constructor
         */
        initialize: function () {
            if (this.el.tagName !== 'INPUT') {
                throw new TypeError('Configuration field el should be set');
            }

            $(this.buttonsSelector).on('click', _.bind(this.clickHandler, this));
        },

        /**
         * On click event handler
         *
         * @param {jQuery.Event} e
         */
        clickHandler: function (e) {
            e.preventDefault();

            var $el = $(e.currentTarget),
                action = $el.data('action'),
                code = $el.data('lang'),
                translationStatus = $el.data('translationStatus') || 0
            ;

            if (_.isUndefined(action)) {
                throw new TypeError('Attribute "data-action" should be set for action button');
            } else if (_.indexOf(['enable', 'disable', 'download'], action) === -1) {
                throw new TypeError('Unknown action');
            }

            if (_.isUndefined(code)) {
                throw new TypeError('Attribute "data-lang" should be set for action button');
            }

            var actionMediator = {
                el: $el,
                action: action,
                code: code,
                translationStatus: translationStatus
            };
            this.performAction(actionMediator);
        },

        /**
         * Perform action for current button
         *
         * @param {Object} actionMediator
         */
        performAction: function (actionMediator) {
            if (actionMediator.action == 'download') {
                var navigation = Navigation.getInstance();
                if (navigation) {
                    navigation.loadingMask.show();
                }

                var url = routing.generate(this.route, {
                    code: actionMediator.code,
                    translationStatus: actionMediator.translationStatus
                });
                $.post(url, _.bind(function () {
                        this.postAction(actionMediator);
                    }, this))
                    .always(_.bind(function (respose, status) {
                        var message;

                        if (navigation) {
                            navigation.loadingMask.hide();
                        }
                        if (status !== 'success') {
                            message = _.isUndefined(respose.message) ? __('unknown') : __(respose.message);
                            message = __('Could not download translations, error: ') + message;
                        } else {
                            message = __('Download finished.');
                        }

                        messenger.notificationFlashMessage(status !== 'success' ? 'error' : 'success', message);
                    }, this));
            } else {
                this.postAction(actionMediator);
            }

        },

        /**
         * Post action callback
         *
         * @param {Object} actionMediator
         */
        postAction: function (actionMediator) {
            var $newButton,
                action = actionMediator.action,
                code = actionMediator.code,
                value = this.$el.val(),
                config = JSON.parse(value ? value : '{}');

            if (action == 'download' || action == 'disable') {
                $newButton = $(this.buttonTemplate({code: code, action: 'enable', label: __('Enable')}));
                config[code] = this.options.STATUS_DOWNLOADED;
            } else if (action == 'enable') {
                $newButton = $(this.buttonTemplate({code: code, action: 'disable', label: __('Disable')}));
                config[code] = this.options.STATUS_ENABLED;
            }

            $($newButton).on('click', _.bind(this.clickHandler, this));
            actionMediator.el.replaceWith($newButton);

            this.$el.val(JSON.stringify(config));
        }
    });
});
