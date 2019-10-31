define(['jquery', 'routing'], function($, routing) {
    'use strict';

    return {
        generate: function(folderId) {
            let url = routing.generate('oro_email_user_emails');
            if (Number(folderId)) {
                const params = $.param({
                    f: {
                        folders: {
                            value: [folderId]
                        }
                    }
                });
                url += '?' + $.param({
                    grid: {
                        'user-email-grid': 'i=1'
                    }
                }) + encodeURIComponent('&' + params);
            }
            return url;
        }
    };
});
