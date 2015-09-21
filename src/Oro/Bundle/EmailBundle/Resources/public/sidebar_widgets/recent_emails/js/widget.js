define(function(require) {
    'use strict';

    var module = require('module');
    return {
        Component: require('../../../js/app/components/sidebar-recent-emails-component'),
        ContentView: require('../../../js/app/views/sidebar-widget/recent-emails/recent-emails-content-view'),
        SetupView: require('../../../js/app/views/sidebar-widget/recent-emails/recent-emails-setup-view'),
        HIDE_REFRESH_BUTTON: false,
        unreadEmailsCount: module.config().unreadEmailsCount
    };
});
