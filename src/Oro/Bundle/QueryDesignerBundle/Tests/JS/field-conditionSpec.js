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

        it('renders default filter', function (done) {
            var $noneFilterTemplate = $('<script type="text/template" id="filter-wrapper-template">\
    <div class="btn-group filter-item oro-drop">\
        <button type="button" class="btn filter-criteria-selector oro-drop-opener oro-dropdown-toggle">\
            <% if (showLabel) { %><%= label %>: <% } %>\
            <strong class="filter-criteria-hint"><%= criteriaHint %></strong>\
            <span class="caret"></span>\
        </button>\
        <% if (canDisable) { %>\
            <a href="<%= nullLink %>" class="disable-filter">\
                <i class="icon-remove hide-text"><%- _.__("Close") %></i>\
            </a>\
        <% } %>\
        <div class="filter-criteria dropdown-menu" />\
    </div>\
</script>\
<script type="text/template" id="none-filter-template">\
    <div><%= popupHint %></div>\
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
                }
            });
            waitForFilter(function (timeout) {
                expect($el.find('.active-filter')).toContainHtml('<div>Choose a column first</div>');
                done();
            });
        });
    });
});
