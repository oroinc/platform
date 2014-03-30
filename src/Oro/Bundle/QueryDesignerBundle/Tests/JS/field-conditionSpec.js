/*global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine*/
define(['jquery', 'text!./Fixture/field-condition/markup.html', 'oroquerydesigner/js/field-condition'],
function ($, markup) {
    'use strict';

    describe('oroquerydesigner/js/field-condition', function () {
        var $el = null;

        beforeEach(function () {
            $el = $('<div>');
            $el.append($(markup));
            $('body').append($el);
        });

        afterEach(function () {
            $el.remove();
            $el = null;
        });

        function waitForFilter(cb) {
            var timeout = 20,
                tick = 1,
                t = timeout,
                html = $el.find('.active-filter').html();
            function wait() {
                t -= tick;
                var current = $el.find('.active-filter').html();
                if ((current !== html) || (t <= 0)) {
                    cb(timeout - t);
                } else {
                    setTimeout(wait, tick);
                }
            }
            setTimeout(wait, tick);
        }

        it('is $ widget', function (done) {
            expect(function () {
                $el.fieldCondition();
                waitForFilter(function () {
                    done();
                });
            }).not.toThrow();
        });

        it('renders empty filter', function (done) {
            $el.data('value', {
                "columnName":"name"
            });
            $el.fieldCondition();
            waitForFilter(function (timeout) {
                expect($el.find('.active-filter')).toContainHtml('<div></div>');
                done();
            });
        });

        it('renders none filter', function (done) {
            var $fieldsLoader = $('<input id="fields_loader"></input>');
            $el.append($fieldsLoader);
            $fieldsLoader.val('OroCRM\\Bundle\\AccountBundle\\Entity\\Account');
            $fieldsLoader.data('fields', [
                {
                    "name": "name",
                    "type": "string",
                    "label": "Account name"
                },
                {
                    "name": "createdAt",
                    "type": "datetime",
                    "label": "Created"
                }
            ]);
            $el.data('value', {
                "columnName":"name",
                "criterion": {
                    "data": {
                    }
                }
            });
            $el.fieldCondition({
                "fieldChoice": {
                    "select2": {
                        "placeholder": "Choose a field...",
                        "formatSelectionTemplate": "<% _.each(obj, function (column, index, list) { %>&#32;<%= column.entity.label %>&nbsp;<b><%= column.label %></b><% if (index < list.length - 1) { %>&nbsp;><% } %><% }) %>"
                    },
                    "util": {},
                    "fieldsLoaderSelector": "#fields_loader"
                },
                "filters": []
            });
            waitForFilter(function (timeout) {
                expect($el.find('.active-filter')).toContainHtml('<div></div>');
                done();
            });
        });

        it('renders choice filter', function (done) {
            var $fieldsLoader = $('<input id="fields_loader"></input>');
            $el.append($fieldsLoader);
            $fieldsLoader.val('OroCRM\\Bundle\\AccountBundle\\Entity\\Account');
            $fieldsLoader.data('fields', [
                {
                    "name": "name",
                    "type": "string",
                    "label": "Account name"
                },
                {
                    "name": "createdAt",
                    "type": "datetime",
                    "label": "Created"
                }
            ]);
            $el.data('value', {
                "columnName": "name",
                "criterion": {
                    "filter": "string",
                    "data": {
                        "value": "a",
                        "type": "1"
                    }
                }
            });
            $el.fieldCondition({
                "fieldChoice": {
                    "select2": {
                        "placeholder": "Choose a field...",
                        "formatSelectionTemplate": "<% _.each(obj, function (column, index, list) { %>&#32;<%= column.entity.label %>&nbsp;<b><%= column.label %></b><% if (index < list.length - 1) { %>&nbsp;><% } %><% }) %>"
                    },
                    "util": {},
                    "fieldsLoaderSelector": "#fields_loader"
                },
                "filters": [
                    {
                        "name": "string",
                        "label": "String",
                        "choices": [
                            {
                                "data": 1,
                                "value": "1",
                                "label": "contains"
                            },
                            {
                                "data": 2,
                                "value": "2",
                                "label": "does not contain"
                            },
                            {
                                "data": 3,
                                "value": "3",
                                "label": "is equal to"
                            },
                            {
                                "data": 4,
                                "value": "4",
                                "label": "starts with"
                            },
                            {
                                "data": 5,
                                "value": "5",
                                "label": "ends with"
                            },
                            {
                                "data": 6,
                                "value": "6",
                                "label": "is any of"
                            },
                            {
                                "data": 7,
                                "value": "7",
                                "label": "is not any of"
                            }
                        ],
                        "applicable": [
                            {
                                "type": "string"
                            },
                            {
                                "type": "text"
                            }
                        ],
                        "type": "string",
                        "templateTheme": "embedded"
                    }
                ]
            });
            waitForFilter(function (timeout) {
                var $f = $el.find('.active-filter');
                expect($f).toContainText('contains');
                expect($f).toContainText('does not contain');
                expect($f).toContainText('is equal to');
                expect($f).toContainText('starts with');
                expect($f).toContainText('ends with');
                expect($f).toContainText('is any of');
                expect($f).toContainText('is not any of');
                done();
            });
        });

        it('replaces filter', function (done) {
            var $fieldsLoader = $('<input id="fields_loader"></input>');
            $el.append($fieldsLoader);
            $fieldsLoader.val('OroCRM\\Bundle\\AccountBundle\\Entity\\Account');
            $fieldsLoader.data('fields', [
                {
                    "name": "name",
                    "type": "string",
                    "label": "Account name"
                },
                {
                    "name": "createdAt",
                    "type": "datetime",
                    "label": "Created"
                }
            ]);
            $el.data('value', {
                "columnName": "name",
                "criterion": {
                    "filter": "string",
                    "data": {
                        "value": "a",
                        "type": "1"
                    }
                }
            });
            $el.fieldCondition({
                "fieldChoice": {
                    "select2": {
                        "placeholder": "Choose a field...",
                        "formatSelectionTemplate": "<% _.each(obj, function (column, index, list) { %>&#32;<%= column.entity.label %>&nbsp;<b><%= column.label %></b><% if (index < list.length - 1) { %>&nbsp;><% } %><% }) %>"
                    },
                    "util": {},
                    "fieldsLoaderSelector": "#fields_loader"
                },
                "filters": [
                    {
                        "name": "string",
                        "label": "String",
                        "choices": [
                            {
                                "data": 1,
                                "value": "1",
                                "label": "contains"
                            },
                            {
                                "data": 2,
                                "value": "2",
                                "label": "does not contain"
                            },
                            {
                                "data": 3,
                                "value": "3",
                                "label": "is equal to"
                            },
                            {
                                "data": 4,
                                "value": "4",
                                "label": "starts with"
                            },
                            {
                                "data": 5,
                                "value": "5",
                                "label": "ends with"
                            },
                            {
                                "data": 6,
                                "value": "6",
                                "label": "is any of"
                            },
                            {
                                "data": 7,
                                "value": "7",
                                "label": "is not any of"
                            }
                        ],
                        "applicable": [
                            {
                                "type": "string"
                            },
                            {
                                "type": "text"
                            }
                        ],
                        "type": "string",
                        "templateTheme": "embedded"
                    },
                    {
                        "name": "datetime",
                        "label": "Datetime",
                        "choices": [
                            {
                                "data": 1,
                                "value": "1",
                                "label": "between"
                            },
                            {
                                "data": 2,
                                "value": "2",
                                "label": "not between"
                            },
                            {
                                "data": 3,
                                "value": "3",
                                "label": "more than"
                            },
                            {
                                "data": 4,
                                "value": "4",
                                "label": "less than"
                            }
                        ],
                        "applicable": [
                            {
                                "type": "datetime"
                            }
                        ],
                        "type": "datetime",
                        "templateTheme": "embedded",
                        "typeValues": {
                            "between": 1,
                            "notBetween": 2,
                            "moreThan": 3,
                            "lessThan": 4
                        },
                        "dateParts": {
                            "value": "value",
                            "dayofweek": "day of week",
                            "week": "week",
                            "day": "day",
                            "month": "month",
                            "quarter": "quarter",
                            "dayofyear": "day of year",
                            "year": "year"
                        },
                        "externalWidgetOptions":{
                            "firstDay":0,
                            "showDatevariables":true,
                            "showTime":true,
                            "showTimepicker":true,
                            "dateVars":{
                                "value":{
                                    "1":"now",
                                    "2":"today",
                                    "3":"start of the week",
                                    "4":"start of the month",
                                    "5":"start of the quarter",
                                    "6":"start of the year"
                                },
                                "dayofweek":{
                                    "10":"current day",
                                    "15":"first day of quarter"
                                },
                                "week":{
                                    "11":"current week"
                                },
                                "day":{
                                    "10":"current day",
                                    "15":"first day of quarter"
                                },
                                "month":{
                                    "12":"current month",
                                    "16":"first month of quarter"
                                },
                                "quarter":{
                                    "13":"current quarter"
                                },
                                "dayofyear":{
                                    "10":"current day",
                                    "15":"first day of quarter"
                                },
                                "year":{
                                    "14":"current year"
                                }
                            }
                        }
                    },
                ]
            });
            waitForFilter(function (timeout) {
                $el.fieldCondition('selectField', 'createdAt');
                waitForFilter(function (timeout) {
                    var $f = $el.find('.active-filter');

                    expect($f).toContainText('between');
                    expect($f).toContainText('not between');
                    expect($f).toContainText('more than');
                    expect($f).toContainText('less than');
                    done();
                });
            });
        });
    });
});
