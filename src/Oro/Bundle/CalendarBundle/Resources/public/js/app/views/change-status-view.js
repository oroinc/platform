/*global define*/
define(function (require) {
    'use strict';

    var ChangeStatusView,
        $ = require('jquery'),
        mediator = require('oroui/js/mediator'),
        BaseView = require('oroui/js/app/views/base/view');

    /**
     * @export orocalendar/js/app/views/change-status-view
     * @extends oroui.app.views.base.Component
     * @class orocalendar.app.views.ChangeStatusView
     */
    ChangeStatusView = BaseView.extend({
        /**
         * @property {Object}
         */
        $element: null,

        /**
         * @constructor
         */
        initialize: function () {
            this.$element = this.$el;
            if (!this.$element) {
                return;
            }

            var self = this;
            this.$element.on('click.' + this.cid, function (e) {
                e.preventDefault();
                $.ajax({
                    url: self.$element.attr('href'),
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
            ChangeStatusView.__super__.dispose.call(this);
        }
    });

    return ChangeStatusView;
});
