/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    'oroui/js/mediator',
    './base/model'
], function (_, mediator, BaseModel) {
    'use strict';

    var PageModel;

    PageModel = BaseModel.extend({
        defaults: {
            content: '',
            scripts: '',
            mainMenu: '',
            userMenu: '',
            breadcrumb: '',

            history: '',
            mostviewed: '',

            title: '',
            titleSerialized: '',
            titleShort: '',

            flashMessages: {},
            showPinButton: false
        },

        /**
         * Fetches data from server
         *  - extends options with required parameters
         *
         * @param {Object=} options
         * @override
         */
        fetch: function (options) {
            var headers, headerId;
            headers = {};
            headerId = mediator.execute('retrieveOption', 'headerId');
            if (headerId) {
                // @TODO discuss if 'x-oro-hash-navigation' header is still necessary
                headers[headerId] = true;
            }

            options = _.defaults(options || {}, {
                accepts: {
                    // @TODO refactor server side action point to accept 'application/json'
                    "json": "*/*"
                },
                headers: headers
            });
            PageModel.__super__.fetch.call(this, options);
        },

        /**
         * Validate attribute
         *  - on redirect attribute existed - triggers invalid message with redirect options
         *
         * @param {Object} attrs
         * @param {Object} options
         * @returns {Object|undefined}
         * @override
         */
        validate: function (attrs, options) {
            var result;
            if (attrs.redirect) {
                result = _.pick(attrs, ['redirect', 'fullRedirect', 'location']);
            }
            return result;
        }
    });

    return PageModel;
});
