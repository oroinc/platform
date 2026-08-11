import $ from 'jquery';
import Backbone from 'backbone';
import ab from 'autobahn';
import Wamp from 'orosync/js/sync/wamp';

describe('orosync/js/sync/wamp', function() {
    let session;

    beforeEach(function() {
        spyOn(ab, 'debug');
        spyOn(ab, 'connect');
        spyOn($, 'ajax');
        spyOn($.fn, 'on');
        session = jasmine.createSpyObj('session', ['subscribe', 'unsubscribe', 'close']);
    });

    describe('create instance', function() {
        let wamp;
        let options;
        beforeEach(function() {
            options = {
                host: '127.0.0.1',
                syncTicketUrl: 'test_url',
                retryDelay: 60000,
                keepReconnectTimeout: 15000,
                keepReconnectRetryDelay: 2000
            };
        });

        it('required options', function() {
            expect(function() {
                wamp = new Wamp();
            }).toThrowError('host option is required');
        });

        it('debug mode', function() {
            wamp = new Wamp(options);
            expect(ab.debug).not.toHaveBeenCalled();
            options.debug = true;
            wamp = new Wamp(options);
            expect(ab.debug).toHaveBeenCalledWith(true, true, true);
        });

        it('connection open', function() {
            $.ajax.and.callFake(function(url, params) {
                params.success({ticket: 'test_ticket'});
            });
            wamp = new Wamp(options);
            expect(ab.connect).toHaveBeenCalledWith(
                jasmine.any(String),
                jasmine.any(Function),
                jasmine.any(Function),
                wamp.options
            );
        });

        it('connection re-open', function() {
            wamp = new Wamp(options);
            // connection established
            wamp.session = session;
            ab.connect.calls.reset();
            wamp.connect();
            expect(ab.connect).not.toHaveBeenCalled();
        });

        it('implements Backbone.Events', function() {
            wamp = new Wamp(options);
            expect(wamp).toEqual(jasmine.objectContaining(Backbone.Events));
        });

        it('handle beforeunload event', function() {
            wamp = new Wamp(options);
            wamp.session = session;
            expect($.fn.on).toHaveBeenCalledWith('beforeunload', jasmine.any(Function));
            // execute callback
            $.fn.on.calls.mostRecent().args[1]();
            expect(session.close).toHaveBeenCalled();
        });

        describe('connection callbacks', function() {
            let onConnect;
            let onHangup;
            beforeEach(function() {
                jasmine.clock().install();
                $.ajax.and.callFake(function(url, params) {
                    params.success({ticket: 'test_ticket'});
                });
                wamp = new Wamp(options);
                spyOn(wamp, 'trigger').and.callThrough();
                spyOn(wamp, 'connect');
                onConnect = ab.connect.calls.mostRecent().args[1];
                onHangup = ab.connect.calls.mostRecent().args[2];
            });

            afterEach(function() {
                jasmine.clock().uninstall();
            });

            it('on connect with empty channels queue', function() {
                wamp.channels = {};
                onConnect(session);
                expect(wamp.session).toBe(session);
                expect(wamp.trigger).toHaveBeenCalledWith('connection_established');
                expect(session.subscribe).not.toHaveBeenCalled();
            });

            it('on connect with queid subscription', function() {
                const callback11 = function() {};
                const callback12 = function() {};
                const callback21 = function() {};
                wamp.channels = {
                    '/some/channel/1': [callback11, callback12],
                    '/some/channel/2': [callback21]
                };
                onConnect(session);
                expect(wamp.session).toBe(session);
                expect(wamp.trigger).toHaveBeenCalledWith('connection_established');
                expect(session.subscribe.calls.count()).toEqual(3);
                expect(session.subscribe).toHaveBeenCalledWith('/some/channel/1', callback11);
                expect(session.subscribe).toHaveBeenCalledWith('/some/channel/1', callback12);
                expect(session.subscribe).toHaveBeenCalledWith('/some/channel/2', callback21);
                expect(session.subscribe).not.toHaveBeenCalledWith('/some/channel/2', callback11);
            });

            it('on hangup peacefully', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(0);
                expect(wamp.session).toBeFalsy();
                expect(wamp.retryCount).toBe(0);
                expect(wamp.disconnectedAt).toBeNull();
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());

                jasmine.clock().tick(wamp.options.keepReconnectTimeout + wamp.options.retryDelay);
                expect(wamp.connect).not.toHaveBeenCalled();
            });

            it('on reconnect resets retryCount, disconnectedAt and errorNotified', function() {
                jasmine.clock().mockDate();
                onConnect(session);
                expect(wamp.session).toBe(session);
                expect(wamp.retryCount).toBe(0);

                onHangup(1);
                expect(wamp.session).toBeFalsy();
                expect(wamp.retryCount).toBe(0);

                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                onHangup(1);
                expect(wamp.retryCount).toBe(1);
                expect(wamp.errorNotified).toBe(true);

                onConnect(session);
                expect(wamp.session).toBe(session);
                expect(wamp.retryCount).toBe(0);
                expect(wamp.disconnectedAt).toBeNull();
                expect(wamp.errorNotified).toBe(false);
            });

            it('on hangup within keepReconnectTimeout retries silently, without connection_lost', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);
                expect(wamp.session).toBeFalsy();
                expect(wamp.retryCount).toBe(0);
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());

                jasmine.clock().tick(wamp.options.keepReconnectRetryDelay - 1);
                expect(wamp.connect).not.toHaveBeenCalled();
                jasmine.clock().tick(1);
                expect(wamp.connect).toHaveBeenCalled();

                for (let i = 0; i < 6; i++) {
                    wamp.connect.calls.reset();
                    onHangup(1);
                    expect(wamp.retryCount).toBe(0);
                    jasmine.clock().tick(wamp.options.keepReconnectRetryDelay);
                    expect(wamp.connect).toHaveBeenCalled();
                }
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());
            });

            it('on hangup past keepReconnectTimeout triggers connection_lost once and keeps retrying', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());

                // real time elapses past keepReconnectTimeout before the next scheduled attempt fails again
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                wamp.connect.calls.reset();

                onHangup(1);
                expect(wamp.session).toBeFalsy();
                expect(wamp.retryCount).toBe(1);
                expect(wamp.trigger).toHaveBeenCalledWith('connection_lost', jasmine.objectContaining({
                    code: 1,
                    retries: 1,
                    delay: wamp.options.retryDelay
                }));

                jasmine.clock().tick(wamp.options.retryDelay - 1);
                expect(wamp.connect).not.toHaveBeenCalled();
                jasmine.clock().tick(1);
                expect(wamp.connect).toHaveBeenCalled();

                // a further failure within the same ongoing outage does not re-trigger connection_lost
                wamp.trigger.calls.reset();
                onHangup(1);
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());
            });

            it('first attempt after keepReconnectTimeout uses a single retryDelay', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);

                for (let i = 0; i < 7; i++) {
                    jasmine.clock().tick(wamp.options.keepReconnectRetryDelay);
                    onHangup(1);
                    expect(wamp.retryCount).toBe(0);
                }
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());

                jasmine.clock().tick(wamp.options.keepReconnectTimeout);
                onHangup(1);

                expect(wamp.retryCount).toBe(1);
                expect(wamp.trigger).toHaveBeenCalledWith('connection_lost', jasmine.objectContaining({
                    retries: 1,
                    delay: wamp.options.retryDelay
                }));
            });

            it('linear backoff for consecutive attempts after keepReconnectTimeout', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                onHangup(1);
                expect(wamp.retryCount).toBe(1);

                wamp.connect.calls.reset();
                wamp.trigger.calls.reset();
                jasmine.clock().tick(wamp.options.retryDelay + 1);
                onHangup(1);

                expect(wamp.retryCount).toBe(2);
                expect(wamp.trigger).not.toHaveBeenCalledWith('connection_lost', jasmine.anything());

                wamp.connect.calls.reset();
                jasmine.clock().tick(2 * wamp.options.retryDelay - 1);
                expect(wamp.connect).not.toHaveBeenCalled();
                jasmine.clock().tick(1);
                expect(wamp.connect).toHaveBeenCalled();
            });

            it('silent retries of a previous outage do not inflate the next outage backoff', function() {
                jasmine.clock().mockDate();
                wamp.session = session;

                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectRetryDelay);
                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectRetryDelay);
                onHangup(1);
                expect(wamp.retryCount).toBe(0);

                onConnect(session);
                expect(wamp.retryCount).toBe(0);
                expect(wamp.disconnectedAt).toBeNull();

                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                onHangup(1);

                expect(wamp.retryCount).toBe(1);
                expect(wamp.trigger).toHaveBeenCalledWith('connection_lost', jasmine.objectContaining({retries: 1}));
            });

            it('with keepReconnectTimeout set to 0 the first failure backs off by one retryDelay', function() {
                const immediateWamp = new Wamp({...options, keepReconnectTimeout: 0});
                spyOn(immediateWamp, 'trigger').and.callThrough();
                spyOn(immediateWamp, 'connect');
                const immediateOnHangup = ab.connect.calls.mostRecent().args[2];

                jasmine.clock().mockDate();
                immediateWamp.session = session;
                immediateOnHangup(1);

                expect(immediateWamp.retryCount).toBe(1);
                expect(immediateWamp.trigger).toHaveBeenCalledWith('connection_lost', jasmine.objectContaining({
                    retries: 1,
                    delay: immediateWamp.options.retryDelay
                }));
            });

            it('CONNECTION_CLOSED during an ongoing outage does not consume a backoff step', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                onHangup(1);
                expect(wamp.retryCount).toBe(1);

                onHangup(0);
                expect(wamp.retryCount).toBe(1);
                expect(wamp.session).toBeFalsy();

                wamp.connect.calls.reset();
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);
                onHangup(1);
                expect(wamp.retryCount).toBe(2);
            });

            it('connection_lost payload overrides autobahn details', function() {
                jasmine.clock().mockDate();
                wamp.session = session;
                onHangup(1);
                jasmine.clock().tick(wamp.options.keepReconnectTimeout + 1);

                onHangup(1, 'msg', {retries: 3, delay: 1});

                expect(wamp.trigger).toHaveBeenCalledWith('connection_lost', jasmine.objectContaining({
                    code: 1,
                    retries: 1,
                    delay: wamp.options.retryDelay
                }));
            });
        });

        describe('subscription handling', function() {
            let wrappedCallback;
            const originalCallback1 = function() {};
            const originalCallback2 = function() {};
            const channel = 'some/channel';
            beforeEach(function() {
                wamp = new Wamp(options);
                wamp.session = session;
            });

            it('subscribe', function() {
                wamp.subscribe(channel, originalCallback1);
                expect(session.subscribe).toHaveBeenCalledWith(channel, jasmine.any(Function));
                wrappedCallback = session.subscribe.calls.mostRecent().args[1];
                expect(wrappedCallback).not.toBe(originalCallback1);
                expect(wrappedCallback.origCallback).toBe(originalCallback1);
                expect(wamp.channels[channel]).toEqual(jasmine.any(Array));
                expect(wamp.channels[channel]).toContain(wrappedCallback);

                wamp.subscribe(channel, originalCallback2);
                wrappedCallback = session.subscribe.calls.mostRecent().args[1];
                expect(wrappedCallback).not.toBe(originalCallback2);
                expect(wrappedCallback.origCallback).toBe(originalCallback2);
                expect(wamp.channels[channel]).toContain(wrappedCallback);
            });

            describe('unsubscribe', function() {
                beforeEach(function() {
                    wamp.subscribe(channel, originalCallback1);
                    wamp.subscribe(channel, originalCallback2);
                    wrappedCallback = session.subscribe.calls.mostRecent().args[1];
                });

                it('with two parameters', function() {
                    expect(wamp.channels[channel].length).toEqual(2);

                    wamp.unsubscribe(channel, originalCallback1);
                    expect(session.unsubscribe).toHaveBeenCalledWith(channel, jasmine.any(Function));
                    expect(wamp.channels[channel]).toContain(wrappedCallback);
                    expect(wamp.channels[channel].length).toEqual(1);

                    wamp.unsubscribe(channel, originalCallback2);
                    expect(session.subscribe).toHaveBeenCalledWith(channel, wrappedCallback);
                    expect(wamp.channels[channel]).toBeUndefined();
                });

                it('by channle', function() {
                    expect(wamp.channels[channel].length).toEqual(2);

                    wamp.unsubscribe(channel);
                    expect(session.unsubscribe).toHaveBeenCalledWith(channel, undefined);
                    expect(wamp.channels[channel]).toBeUndefined();
                });
            });
        });
    });
});
