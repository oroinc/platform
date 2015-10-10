define(['jquery', 'routing'], function($, routing) {
    'use strict';

    return {
        generate: function(folderId) {
            var url = routing.generate('oro_email_user_emails');
            if (Number(folderId)) {
                url += '?' + $.param({
                    'grid': {
                        'user-email-grid': 'i=1'
                    }
                }) + encodeURIComponent('&' + $.param({
                    'f': {
                        'folders': {
                            'value': [folderId]
                        }
                    }
                }));
            }
            return url;
        }
    };
});
