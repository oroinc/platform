import $ from 'jquery';
import _ from 'underscore';
import Backbone from 'backbone';
import ab from 'autobahn';

const defaultOptions = {
    port: 80,
    debug: false,
    path: '',
    keepReconnectTimeout: 15000,
    keepReconnectRetryDelay: 2000
};

/**
 * Wraps callback in order to make it compatible with autobahn event callback
 */
function wrapCallback(callback) {
    const wrapper = function(channel, attributes) {
        callback(attributes);
    };
    wrapper.origCallback = callback;
    return wrapper;
}

/**
 * Synchronizer service build over WAMP (autobahn.js implementation)
 *
 * @constructor
 * @param {Object} options to configure service
 * @param {string} options.secure is wss protocol should be used, otherwise will be used ws protocol
 * @param {string} options.host is required
 * @param {number=} options.port default is 80
 * @param {number=} options.retryDelay time before next reconnection attempt, default is 5000 (5s)
 * @param {number=} options.keepReconnectTimeout time after a disconnect during which reconnects
 *      are retried silently, without surfacing an error to the user, default is 15000 (15s)
 * @param {number=} options.keepReconnectRetryDelay time before a reconnection attempt while still within
 *      keepReconnectTimeout, default is 2000 (2s)
 * @param {boolean=} options.skipSubprotocolCheck, default is false
 * @param {boolean=} options.skipSubprotocolAnnounce, default is false
 * @param {boolean=} options.debug, default is false
 * @param {string} options.syncTicketUrl
 *
 * @export  orosync/js/sync/wamp
 * @class   orosync.sync.Wamp
 */
function Wamp(options) {
    this.options = _.extend({}, defaultOptions, options);
    this.maxRetries = this.options.maxRetries;

    // set 0 for autobahn maxRetries count as the reconnects was done with onHangup method of Wamp object
    this.options.maxRetries = 0;

    if (!this.options.host) {
        throw new Error('host option is required');
    }
    this.channels = {};
    if (this.options.debug) {
        ab.debug(true, true, true);
    }
    this.connect();
    // fixes premature connection close in FF on page reload
    $(window).on('beforeunload', () => {
        if (this.session) {
            this.session.close();
        }
    });
}

Wamp.prototype = {
    // number of retry reconnects
    retryCount: 0,

    // timestamp (ms) of the first failed attempt in the current outage, null while connected
    disconnectedAt: null,

    // true once a "connection lost" message has been shown for the current outage
    errorNotified: false,

    /**
     * Whether the current outage is still within keepReconnectTimeout
     *
     * @return {boolean}
     */
    isWithinKeepReconnectTimeout: function() {
        return this.disconnectedAt !== null &&
            (Date.now() - this.disconnectedAt) < this.options.keepReconnectTimeout;
    },

    /**
     * Initiate connection process
     */
    connect: function() {
        if (!this.session) {
            $.ajax(this.options.syncTicketUrl, {
                method: 'POST',
                errorHandlerMessage: !this.isWithinKeepReconnectTimeout(),
                success: (function(response) {
                    const protocol = this.options.secure ? 'wss' : 'ws';
                    let wsuri = [
                        protocol,
                        '://',
                        this.options.host,
                        ':',
                        this.options.port,
                        '/',
                        this.options.path.replace(/\/+$/, '')
                    ].join('');
                    wsuri = wsuri + '?ticket=' + encodeURIComponent(response.ticket);
                    ab.connect(wsuri, this.onConnect.bind(this), this.onHangup.bind(this), this.options);
                }).bind(this),
                error: (function() {
                    this.onHangup(ab.CONNECTION_UNSUPPORTED);
                }).bind(this),
                dataType: 'json'
            });
        }
    },

    /**
     * Subscribes update callback function on a channel
     *
     * @param {string} channel is an URL which broadcasts updates
     * @param {function (Object)} callback is a function which accepts JSON
     *      with attributes' values and performs update
     */
    subscribe: function(channel, callback) {
        callback = wrapCallback(callback);
        (this.channels[channel] = this.channels[channel] || []).push(callback);
        if (this.session) {
            this.session.subscribe(channel, callback);
        }
    },

    /**
     * Removes subscription of update callback function for a channel
     *
     * @param {string} channel is an URL which broadcasts updates
     * @param {function (Object)=} callback an optional parameter,
     *      if was no function corresponded then removes all callbacks for a channel
     */
    unsubscribe: function(channel, callback) {
        let callbacks = this.channels[channel];
        if (!callbacks) {
            return;
        }
        if (callback) {
            // maps corresponded callback to a wrapped one
            callback = _.findWhere(callbacks, {origCallback: callback});
            // removes that callback from collection
            callbacks = this.channels[channel] = _.without(callbacks, callback);
        }
        if (!callbacks.length || !callback) {
            delete this.channels[channel];
        }
        if (this.session) {
            try {
                this.session.unsubscribe(channel, callback);
            } catch (e) {}
        }
    },

    /**
     * Handler on losing connection
     *
     * @param {number} code
     *      CONNECTION_CLOSED = 0
     *      CONNECTION_LOST = 1
     *      CONNECTION_RETRIES_EXCEEDED = 2
     *      CONNECTION_UNREACHABLE = 3
     *      CONNECTION_UNSUPPORTED = 4
     *      CONNECTION_UNREACHABLE_SCHEDULED_RECONNECT = 5
     *      CONNECTION_LOST_SCHEDULED_RECONNECT = 6
     * @param {string} msg text message
     * @param {Object} details
     * @param {number} details.delay in ms, before next reconnect attempt
     * @param {number} details.retries number of scheduled attempt
     */
    onHangup: function(code, msg, details) {
        if (code !== ab.CONNECTION_CLOSED) {
            if (this.disconnectedAt === null) {
                this.disconnectedAt = Date.now();
            }

            let delay = this.options.keepReconnectRetryDelay;

            if (!this.isWithinKeepReconnectTimeout()) {
                this.retryCount += 1;
                delay = this.retryCount * this.options.retryDelay;
                details = _.extend(
                    details || {},
                    {retries: this.retryCount, delay: delay}
                );

                if (!this.errorNotified) {
                    this.errorNotified = true;
                    this.trigger('connection_lost', _.extend({code: code}, details));
                }
            }

            window.setTimeout(() => {
                this.connect();
            }, delay);
        }

        this.session = null;
    },

    /**
     * Handler on start connection
     * if list of subscriptions is not empty, auto subscribe all of them
     */
    onConnect: function(session) {
        this.session = session;
        this.retryCount = 0;
        this.disconnectedAt = null;
        this.errorNotified = false;
        this.trigger('connection_established');
        _.each(this.channels, function(callbacks, channel) {
            _.each(callbacks, function(callback) {
                session.subscribe(channel, callback);
            });
        });
    }
};

_.extend(Wamp.prototype, Backbone.Events);

export default Wamp;
