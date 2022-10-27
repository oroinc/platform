define(function(require) {
    'use strict';
    const UnloadMessagesGroup = require('./unload-messages-group');
    const apiAccessorUnloadMessagesGroup = new UnloadMessagesGroup({});
    return apiAccessorUnloadMessagesGroup;
});
