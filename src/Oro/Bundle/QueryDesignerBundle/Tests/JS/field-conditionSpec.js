/*global define, require, describe, it, expect, beforeEach, afterEach, spyOn, jasmine*/
define(['jquery', 'oroquerydesigner/js/field-condition'], function ($) {
    'use strict';

    describe('oroquerydesigner/js/field-condition', function () {
        var $el = null;

        beforeEach(function () {
            $el = $('<div>');
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
            var $noneFilterTemplate = $('\
<script type="text/template" id="none-filter-template">\
</script>');

            $el.append($noneFilterTemplate);

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
                expect($el.find('.active-filter')).toContainHtml('<div></div>');
                done();
            });
        });

        it('renders choice filter', function (done) {
            var $noneFilterTemplate = $('\
<script type="text/template" id="choice-filter-template-embedded">\
    <span> field value </span>\
    <div class="dropdown">\
        <a class="dropdown-toggle" data-toggle="dropdown" href="#"><%= selectedChoiceLabel %></a>:\
        <ul class="dropdown-menu">\
            <% _.each(choices, function (option) { %>\
            <li<% if (selectedChoice == option.value) { %> class="active"<% } %>>\
                <a class="choice_value" href="#" data-value="<%= option.value %>"><%= option.label %></a>\
            </li>\
            <% }); %>\
        </ul>\
        <input type="text" name="value" value="<%- value %>">\
        <input class="name_input" type="hidden" name="<%= name %>" id="<%= name %>" value="<%- selectedChoice %>"/>\
    </div>\
</script>\
');

            $el.append($noneFilterTemplate);

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

    });
});
