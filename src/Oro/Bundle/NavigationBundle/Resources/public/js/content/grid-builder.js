define(['underscore', 'oro/content-manager'], function (_, ContentManager) {
    'use strict';

    var initialized = false,
        methods = {
            initHandler: function ($el) {
                this.metadata = $el.data('metadata') || {};

                if (!_.isUndefined(this.metadata.options) && _.isArray(this.metadata.options.contentTags || [])) {
                    ContentManager.tagContent(this.metadata.options.contentTags);
                }

                initialized = true;
            }
        };

    return {
        init: function ($el) {
            methods.initHandler($el);
        }
    };
});
