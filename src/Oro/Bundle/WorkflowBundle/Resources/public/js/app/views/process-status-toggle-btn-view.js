import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import $ from 'jquery';

const ProcessStatusToggleBtnView = BaseView.extend({
    events: {
        'click [data-role="status-toggle"]': 'onStatusToggle'
    },

    /**
     * @inheritdoc
     */
    constructor: function ProcessStatusToggleBtnView(options) {
        ProcessStatusToggleBtnView.__super__.constructor.call(this, options);
    },

    onStatusToggle: function(e) {
        e.preventDefault();

        $.ajax({
            url: e.currentTarget.pathname,
            method: 'POST',
            success: function(response) {
                if (response.message) {
                    mediator.execute('showFlashMessage', 'success', response.message, {afterReload: true});
                }
                mediator.execute('refreshPage');
            }
        });
    }
});

export default ProcessStatusToggleBtnView;
