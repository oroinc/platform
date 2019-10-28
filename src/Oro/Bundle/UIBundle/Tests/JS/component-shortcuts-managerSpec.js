/* global requirejs */
// require({
//     config: {
//         'oroui/js/component-shortcuts-manager': {
//             reservedKeys: ['options', 'testOption']
//         }
//     }
// });

define(['underscore'], function(_) {
    'use strict';

    var componentShortcutsManager;

    var testWidgetConfiguration = {
        moduleName: 'test-path-to-module',
        options: {
            widgetName: 'test-path-to-widget'
        }
    };

    xdescribe('Component Shortcuts Manager', function() {
        beforeEach(function(done) {
            requirejs.undef('oroui/js/component-shortcuts-manager');
            require(['oroui/js/component-shortcuts-manager'], function(m) {
                componentShortcutsManager = m;
                done();
            });
        });

        it('Initialize manager with custom module config', function() {
            expect(componentShortcutsManager.reservedKeys).toEqual(['options', 'testOption']);
        });

        it('Add shortcut', function() {
            expect(componentShortcutsManager.getAll()).toEqual({});

            componentShortcutsManager.add('test', testWidgetConfiguration);

            expect(componentShortcutsManager.getAll()).toEqual({
                test: testWidgetConfiguration
            });
        });

        it('Override existing shortcut', function() {
            expect(componentShortcutsManager.getAll()).toEqual({});

            componentShortcutsManager.add('test', testWidgetConfiguration);

            expect(function() {
                componentShortcutsManager.add('test', testWidgetConfiguration);
            }).toThrow(
                new Error('Component shortcut `test` already exists!')
            );
        });

        it('Add shortcut with reserved key', function() {
            expect(function() {
                componentShortcutsManager.add('options', testWidgetConfiguration);
            }).toThrow(
                new Error('Component shortcut `options` is reserved!')
            );
        });

        it('Remove shortcut', function() {
            componentShortcutsManager.add('test', testWidgetConfiguration);

            expect(componentShortcutsManager.getAll()).toEqual({
                test: testWidgetConfiguration
            });

            componentShortcutsManager.remove('test');

            expect(componentShortcutsManager.getAll()).toEqual({});
        });

        it('Get all shortcuts', function() {
            var iterations = 5;

            for (var i = 0; i < iterations; i++) {
                componentShortcutsManager.add('test_' + i, testWidgetConfiguration);
            }

            expect(_.size(componentShortcutsManager.getAll())).toEqual(iterations);
        });

        it('Get component object data', function() {
            componentShortcutsManager.add('test', testWidgetConfiguration);
            var testPageComponentOptions = _.extend({}, testWidgetConfiguration.options, {test: true});
            var shortcut = componentShortcutsManager.getAll()['test'];
            var elemData = {};
            elemData[shortcut.dataKey] = {test: true};

            expect(componentShortcutsManager.getComponentData(shortcut, elemData)).toEqual({
                pageComponentModule: testWidgetConfiguration.moduleName,
                pageComponentOptions: testPageComponentOptions
            });
        });

        it('Get component object data - simple', function() {
            var shortcut = {
                dataKey: 'pageComponentTest'
            };
            var elemData = {};
            elemData[shortcut.dataKey] = 'component-name';
            expect(componentShortcutsManager.getComponentData(shortcut, elemData)).toEqual({
                pageComponentModule: 'component-name',
                pageComponentOptions: {}
            });
        });

        it('Get component object data - scalar', function() {
            componentShortcutsManager.add('test', _.extend({}, testWidgetConfiguration, {scalarOption: 'height'}));
            var testPageComponentOptions = _.extend({}, testWidgetConfiguration.options, {height: 80});
            var shortcut = componentShortcutsManager.getAll()['test'];
            var elemData = {};
            elemData[shortcut.dataKey] = 80;

            expect(componentShortcutsManager.getComponentData(shortcut, elemData)).toEqual({
                pageComponentModule: testWidgetConfiguration.moduleName,
                pageComponentOptions: testPageComponentOptions
            });
        });

        it('Get component resolved shortcut data key/attr', function() {
            componentShortcutsManager.add('test-shortcut-module', testWidgetConfiguration);

            expect(componentShortcutsManager.getAll()['test-shortcut-module'].dataKey)
                .toEqual('pageComponentTestShortcutModule');

            expect(componentShortcutsManager.getAll()['test-shortcut-module'].dataAttr)
                .toEqual('data-page-component-test-shortcut-module');
        });
    });
});
