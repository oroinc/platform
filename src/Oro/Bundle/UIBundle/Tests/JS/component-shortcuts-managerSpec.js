define(function(require) {
    'use strict';

    const _ = require('underscore');
    const componentShortcutsManagerModuleInjector = require('inject-loader!oroui/js/component-shortcuts-manager');

    const testWidgetConfiguration = {
        moduleName: 'test-path-to-module',
        options: {
            widgetName: 'test-path-to-widget'
        }
    };

    describe('Component Shortcuts Manager', function() {
        let componentShortcutsManager;

        beforeEach(function() {
            componentShortcutsManager = componentShortcutsManagerModuleInjector({
                'module-config': {
                    'default': () => ({reservedKeys: ['options', 'someReservedName']})
                }
            });
        });

        it('Initialize manager with custom module config', function() {
            expect(() => {
                componentShortcutsManager.add('someComponent', {
                    moduleName: 'some/module/name'
                });
            }).not.toThrowError();

            expect(() => {
                componentShortcutsManager.add('someReservedName', {
                    moduleName: 'some/module/name'
                });
            }).toThrow(
                new Error('Component shortcut `someReservedName` is reserved!')
            );
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
            const iterations = 5;

            for (let i = 0; i < iterations; i++) {
                componentShortcutsManager.add('test_' + i, testWidgetConfiguration);
            }

            expect(_.size(componentShortcutsManager.getAll())).toEqual(iterations);
        });

        it('Get component object data', function() {
            componentShortcutsManager.add('test', testWidgetConfiguration);
            const testPageComponentOptions = _.extend({}, testWidgetConfiguration.options, {test: true});
            const shortcut = componentShortcutsManager.getAll()['test'];
            const elemData = {};
            elemData[shortcut.dataKey] = {test: true};

            expect(componentShortcutsManager.getComponentData(shortcut, elemData)).toEqual({
                pageComponentModule: testWidgetConfiguration.moduleName,
                pageComponentOptions: testPageComponentOptions
            });
        });

        it('Get component object data - simple', function() {
            const shortcut = {
                dataKey: 'pageComponentTest'
            };
            const elemData = {};
            elemData[shortcut.dataKey] = 'component-name';
            expect(componentShortcutsManager.getComponentData(shortcut, elemData)).toEqual({
                pageComponentModule: 'component-name',
                pageComponentOptions: {}
            });
        });

        it('Get component object data - scalar', function() {
            componentShortcutsManager.add('test', _.extend({}, testWidgetConfiguration, {scalarOption: 'height'}));
            const testPageComponentOptions = _.extend({}, testWidgetConfiguration.options, {height: 80});
            const shortcut = componentShortcutsManager.getAll()['test'];
            const elemData = {};
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
