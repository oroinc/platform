/*global define*/
define(function (require) {
    'use strict';

    var ChangeStatusComponent,
        $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @export orocalendar/js/app/components/change-status-component
     * @extends oroui.app.components.base.Component
     * @class orocalendar.app.components.ChangeStatusComponent
     */
    ChangeStatusComponent = BaseComponent.extend({
        /**
         * @property {Object}
         */
        $element: null,

        /**
         * @inheritDoc
         */
        initialize: function (options) {
            this.$element = options._sourceElement;
            if (!this.$element) {
                return;
            }

            var self = this;
            this.$element.on('click.' + this.cid, function (e) {
                e.preventDefault();
                $.ajax({
                    url: self.element.attr('href'),
                    type: 'GET',
                    success: function() {
                        mediator.execute('refreshPage');
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
            if (!this.disposed && this.$element) {
                this.$element.off('.' + this.cid);
            }
            ChangeStatusComponent.__super__.dispose.call(this);
        }
    });

    return ChangeStatusComponent;
});
