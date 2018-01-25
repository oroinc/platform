define(function(require) {
    'use strict';

    var $ = require('jquery');
    var builder = require('orodatagrid/js/row-select-for-widget/builder');

    describe('orodatagrid/js/row-select-for-widget/builder', function() {
        function getOptions(rowSelectForWidgetOptions) {
            return {
                gridBuildersOptions: {
                    rowSelectForWidget: rowSelectForWidgetOptions
                },
                metadata: {
                    options: {}
                }
            };
        }

        it('processDatagridOptions() - multiSelect flag set to true', function() {
            var options = getOptions({multiSelect: true});

            builder.processDatagridOptions($.Deferred(), options);

            expect(options.metadata.options.multiSelectRowEnabled).toBe(true);
            expect(options.metadata.options.rowClickAction).toBeUndefined();
        });

        it('processDatagridOptions() - multiSelect flag set to false', function() {
            var options = getOptions({
                multiSelect: false,
                wid: '123-345234-2345'
            });

            builder.processDatagridOptions($.Deferred(), options);

            expect(options.metadata.options.multiSelectRowEnabled).toBeUndefined();
            expect(typeof options.metadata.options.rowClickAction).toBe('function');
        });

        it('processDatagridOptions() - missing wid option ', function() {
            var options = getOptions({multiSelect: false});

            var functionThatThrows = function() {
                return builder.processDatagridOptions($.Deferred(), options);
            };

            expect(functionThatThrows).toThrow(Error('"wid" has to be defined'));
        });
    });
});
