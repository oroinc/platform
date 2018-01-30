define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('jquery');
    var ComponentManager = require('oroui/js/app/components/component-manager');
    var requirejsExposure = require('requirejs-exposure');
    var exposure = requirejsExposure.disclose('oroui/js/app/components/component-manager');
    var componentsLoadModule = require('../../Fixture/app/components/component-manager/components-loader-mock');
    require('oroui/js/app/modules/component-shortcuts-module');
    require('orofrontend/js/app/modules/component-shortcuts-module');

    function dataAttr(obj) {
        return JSON.stringify(obj).replace(/"/g, '&quot;');
    }

    describe('Component Manager', function() {
        it('Initialize widget placed inside own separated layout', function() {
            window.setFixtures([
                '<div id="container" data-layout="separate">',
                '<div class="widget_container" data-layout="separate">',
                '<div data-page-component-view="test-view"></div>',
                '</div>',
                '</div>'
            ].join(''));

            var manager = new ComponentManager($('#container'));
            var elements = manager._collectElements();

            expect(elements.length).toEqual(0);
        });

        it('Initialize widget', function() {
            window.setFixtures([
                '<div id="container" data-layout="separate">',
                '<div data-page-component-view="test-view"></div>',
                '</div>'
            ].join(''));

            var manager = new ComponentManager($('#container'));
            var elements = manager._collectElements();
            var $element = $(elements[0]);

            expect(elements.length).toEqual(1);
            expect($element.data()).toEqual({
                pageComponentModule: 'oroui/js/app/components/view-component',
                pageComponentOptions: {
                    view: 'test-view'
                }
            });
        });

        it('Initialize shortcut widget', function() {
            window.setFixtures([
                '<div id="container" data-layout="separate">',
                '<div data-page-component-print-page=""></div>',
                '</div>'
            ].join(''));

            var manager = new ComponentManager($('#container'));
            var elements = manager._collectElements();
            var $element = $(elements[0]);

            expect(elements.length).toEqual(1);
            expect($element.data()).toEqual({
                pageComponentModule: 'oroui/js/app/components/jquery-widget-component',
                pageComponentOptions: {
                    printPage: '',
                    widgetModule: 'orofrontend/blank/js/widgets/print-page-widget'
                }
            });
        });

        describe('required sibling components', function() {
            var tools = exposure.retrieve('tools');
            var originalLoadModules = tools.loadModules;

            beforeEach(function() {
                tools.loadModules = jasmine.createSpy().and.callFake(componentsLoadModule);
            });

            afterEach(function() {
                exposure.retrieve('tools').loadModules = originalLoadModules;
            });

            describe('override required componentName with options', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-c" ' +
                            'data-page-component-module="js/needs-a-component" ' +
                            'data-page-component-options="' + dataAttr({
                            relatedSiblingComponents: {
                                componentA: 'component-e'// change component name of dependent on
                            }
                        }) + '"></div>',

                        '<div data-page-component-name="component-e" ' +
                                'data-page-component-module="js/no-needs-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    manager.init().then(done);
                });

                it('compare components', function() {
                    expect(manager.get('component-c').componentA).toBe(manager.get('component-e'));
                });
            });

            describe('remove dependency over component extend', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-d" ' +
                            'data-page-component-module="js/extend-no-need-a-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    manager.init().then(done);
                });

                it('compare components', function() {
                    expect(manager.get('component-d').componentA).toBeUndefined();
                });
            });

            describe('complex dependencies', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/needs-b-component"></div>',
                        '<div data-page-component-name="component-b" ' +
                            'data-page-component-module="js/needs-ce-component"></div>',
                        '<div data-page-component-name="component-c" ' +
                            'data-page-component-module="js/extend-no-need-a-component"></div>',
                        '<div data-page-component-name="component-e" ' +
                            'data-page-component-module="js/no-needs-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    manager.init().then(done);
                });

                it('compare components', function() {
                    expect(manager.get('component-a').componentB).toBe(manager.get('component-b'));
                    expect(manager.get('component-b').componentC).toBe(manager.get('component-c'));
                    expect(manager.get('component-b').componentE).toBe(manager.get('component-e'));
                });
            });

            describe('missing required sibling component', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/needs-b-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    manager.init().then(done);
                });

                it('has to be undefined', function() {
                    expect(manager.get('component-a').componentB).toBeUndefined();
                });
            });

            describe('options parameter is not able to remove dependency', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/needs-b-component" ' +
                            'data-page-component-options="' + dataAttr({
                            relatedSiblingComponents: {
                                componentB: false // attempt to remove dependency over option
                            }
                        }) + '"></div>',
                        '<div data-page-component-name="component-b" ' +
                            'data-page-component-module="js/no-needs-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    manager.init().then(done);
                });

                it('reference on sibling component nevertheless established', function() {
                    expect(manager.get('component-a').componentB).toBe(manager.get('component-b'));
                });
            });

            describe('circular dependency', function() {
                var manager;
                beforeEach(function(done) {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/needs-b-component"></div>',
                        '<div data-page-component-name="component-b" ' +
                            'data-page-component-module="js/needs-ce-component"></div>',
                        '<div data-page-component-name="component-c" ' +
                            'data-page-component-module="js/needs-a-component"></div>',
                        '<div data-page-component-name="component-e" ' +
                            'data-page-component-module="js/no-needs-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    spyOn(manager, '_handleError');
                    manager.init().then(done);
                });

                it('check error', function() {
                    expect(manager._handleError).toHaveBeenCalled();
                    expect(manager._handleError.calls.mostRecent().args[1].message).toContain('circular dependency');
                });
            });
        });
    });
});
