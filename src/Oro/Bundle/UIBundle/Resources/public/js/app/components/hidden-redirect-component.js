/*global define*/
define(function (require) {
    'use strict';

    var HiddenRedirectComponent,
        $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export oroui/js/app/components/hidden-redirect-component
     * @extends oroui.app.components.base.Component
     * @class oroui.app.components.HiddenRedirectComponent
     */
    HiddenRedirectComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        element: null,

        /**
         * @property {string}
         */
        type: 'info',

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            if (options.type) {
                this.type = options.type;
            }

            var self = this;
            this.element.on('click.' + this.cid, function (e) {
                e.preventDefault();
                $.ajax({
                    url: self.element.attr('href'),
                    type: 'GET',
                    success: function(response) {
                        self._processResponse(response.url, response.message);
                    },
                    error: function(xhr) {
                        Error.handle({}, xhr, {enforce: true});
                    }
                });
                return false;
            });
        },

        /**
         * @inheritDoc
         */
        dispose: function () {
            if (this.disposed || !this.element) {
                return;
            }

            this.element.off('.' + this.cid);

            HiddenRedirectComponent.__super__.dispose.call(this);
        },

        /**
         * @param {string|null} url
         * @param {string|null} message
         */
        _processResponse: function(url, message) {
            if (url) {
                if (message) {
                    var self = this;
                    mediator.once('page:afterChange', function() {
                        self._showMessage(self.type, message);
                    });
                }

                if (mediator.execute('compareUrl', url)) {
                    mediator.execute('refreshPage');
                } else {
                    mediator.execute('redirectTo', {url: url});
                }
            } else if (message) {
                this._showMessage(this.type, message);
            }
        },

        /**
         * @param {string} type
         * @param {string} message
         */
        _showMessage: function(type, message) {
            mediator.execute('showFlashMessage', type, message);
        }
    });

    return HiddenRedirectComponent;
});
