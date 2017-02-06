define(function(require) {
    'use strict';

    var _ = require('underscore');
    var moment = require('moment');

    describe('method `tz` with second parameter', function() {
        var dateTimeFormat = 'YYYY-MM-DD[T]HH:mm:ss';
        var dateTimeTZFormat = 'YYYY-MM-DD[T]HH:mm:ssZZ';
        var dates = ['2016-01-01T01:00:00', '2016-05-10T12:00:00', '2016-10-30T18:00:00'];
        var timezones = ['America/Los_Angeles', 'UTC', 'Europe/Kiev', 'Asia/Tokyo'];
        var offsets = ['', '-10:00', '-01:00', '+5:00'];
        describe('should change timezone without changing time', function() {
            _.each(dates, function(date) {
                _.each(timezones, function(timezone) {
                    _.each(offsets, function(offset) {
                        var dateWithOffset = date + offset;
                        it('at ' + dateWithOffset, function() {
                            var expectedMoment = moment.tz(date, dateTimeFormat, true, timezone);
                            expect(
                                moment(dateWithOffset, dateTimeTZFormat).tz(timezone, true).format(dateTimeTZFormat)
                            ).toBe(expectedMoment.format(dateTimeTZFormat));
                        });
                    });
                });
            });
        });

        describe('should change timezone (in instance created with tz name) without changing time', function() {
            _.each(dates, function(date) {
                _.each(timezones, function(timezone) {
                    _.each(timezones, function(initTZ) {
                        it('at ' + date + ' (' + initTZ + ')', function() {
                            var expectedMoment = moment.tz(date, dateTimeFormat, true, timezone);
                            expect(moment.tz(date, dateTimeFormat, true, initTZ).tz(timezone, true)
                                .format(dateTimeTZFormat)).toBe(expectedMoment.format(dateTimeTZFormat));
                        });
                    });
                });
            });
        });

        describe('should work correctly in case', function() {
            it('long transformation chain', function() {
                var momentInst = moment('2016-07-04T20:00:00+01:00', dateTimeTZFormat, true);
                momentInst.tz('Europe/Kiev', true).add(240, 'minutes').tz('Asia/Tokyo', true).add(-5, 'hours')
                    .tz('UTC', true).add(60, 'minutes').tz('America/Los_Angeles', true);
                expect(momentInst.format(dateTimeTZFormat)).toBe('2016-07-04T20:00:00-0700');
            });
        });

        describe('should take in account daylight saving time', function() {
            it('of USA', function() {
                expect(moment('2016-03-13T01:00:00', dateTimeFormat, true)
                    .tz('America/Los_Angeles', true).format(dateTimeTZFormat)).toBe('2016-03-13T01:00:00-0800');
                expect(moment('2016-03-13T02:00:00', dateTimeFormat, true)
                    .tz('America/Los_Angeles', true).format(dateTimeTZFormat)).toBe('2016-03-13T03:00:00-0700');
                expect(moment('2016-03-13T03:00:00', dateTimeFormat, true)
                    .tz('America/Los_Angeles', true).format(dateTimeTZFormat)).toBe('2016-03-13T03:00:00-0700');
                expect(moment('2016-11-06T01:00:00', dateTimeFormat, true)
                    .tz('America/Los_Angeles', true).format(dateTimeTZFormat)).toBe('2016-11-06T01:00:00-0700');
                expect(moment('2016-11-06T02:00:00', dateTimeFormat, true)
                    .tz('America/Los_Angeles', true).format(dateTimeTZFormat)).toBe('2016-11-06T02:00:00-0800');
            });

            it('of Ukraine', function() {
                expect(moment('2016-03-27T02:00:00', dateTimeFormat, true)
                    .tz('Europe/Kiev', true).format(dateTimeTZFormat)).toBe('2016-03-27T02:00:00+0200');
                expect(moment('2016-03-27T03:00:00', dateTimeFormat, true)
                    .tz('Europe/Kiev', true).format(dateTimeTZFormat)).toBe('2016-03-27T04:00:00+0300');
                expect(moment('2016-03-27T04:00:00', dateTimeFormat, true)
                    .tz('Europe/Kiev', true).format(dateTimeTZFormat)).toBe('2016-03-27T04:00:00+0300');
                expect(moment('2016-10-30T03:00:00', dateTimeFormat, true)
                    .tz('Europe/Kiev', true).format(dateTimeTZFormat)).toBe('2016-10-30T03:00:00+0300');
                expect(moment('2016-10-30T04:00:00', dateTimeFormat, true)
                    .tz('Europe/Kiev', true).format(dateTimeTZFormat)).toBe('2016-10-30T04:00:00+0200');
            });
        });
    });
});
