define([
    'underscore',
    'oroui/js/mediator',
    './base/model'
], function(_, mediator, BaseModel) {
    'use strict';

    var PageModel;

    PageModel = BaseModel.extend({
        defaults: {
            currentRoute: '',

            title: '',
            titleSerialized: '',
            titleShort: '',

            flashMessages: {},
            showPinButton: false
        },

        /**
         * @inheritDoc
         */
        constructor: function PageModel() {
            PageModel.__super__.constructor.apply(this, arguments);
        },

        /**
         * Fetches data from server
         *  - extends options with required parameters
         *
         * @param {Object=} options
         * @override
         */
        fetch: function(options) {
            options = this._extendOptions(options);
            PageModel.__super__.fetch.call(this, options);
        },

        /**
         * Saves model
         *  - extends options with required parameters
         *
         * @param key
         * @param value
         * @param options
         * @returns {XMLHttpRequest}
         */
        save: function(key, value, options) {
            if (key === null || key === void 0 || typeof key === 'object') {
                options = value;
            }
            options = this._extendOptions(options);
            return PageModel.__super__.save.call(this, key, value, options);
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
        validate: function(attrs, options) {
            var result;
            if (attrs.redirect) {
                result = _.pick(attrs, ['redirect', 'fullRedirect', 'location']);
            }
            return result;
        },

        /**
         * Adds extra options
         *
         * @param {Object} options
         * @returns {Object}
         * @private
         */
        _extendOptions: function(options) {
            var headerId;
            options = options || {};

            headerId = mediator.execute('retrieveOption', 'headerId');
            if (headerId) {
                options.headers = options.headers || {};
                options.headers[headerId] = true;
            }

            return options;
        }
    });

    return PageModel;
});
