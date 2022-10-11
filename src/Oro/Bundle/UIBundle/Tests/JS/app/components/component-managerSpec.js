define(function(require) {
    'use strict';

    require('jasmine-jquery');
    const $ = require('jquery');
    const componentManagerModuleInjector = require('inject-loader!oroui/js/app/components/component-manager');
    const componentsLoadModule = require('../../Fixture/app/components/component-manager/components-loader-mock');
    require('oroui/js/app/modules/component-shortcuts-module');
    require('orofrontend/js/app/modules/component-shortcuts-module');

    function dataAttr(obj) {
        return JSON.stringify(obj).replace(/"/g, '&quot;');
    }

    describe('Component Manager', function() {
        let ComponentManager;

        beforeEach(function() {
            ComponentManager = componentManagerModuleInjector({
                'oroui/js/app/services/load-modules': jasmine.createSpy().and.callFake(componentsLoadModule)
            });
        });

        it('Initialize widget placed inside own separated layout', function() {
            window.setFixtures([
                '<div id="container" data-layout="separate">',
                '<div class="widget_container" data-layout="separate">',
                '<div data-page-component-view="test-view"></div>',
                '</div>',
                '</div>'
            ].join(''));

            const manager = new ComponentManager($('#container'));
            const elements = manager._collectElements();

            expect(elements.length).toEqual(0);
        });

        it('Initialize widget', function() {
            window.setFixtures([
                '<div id="container" data-layout="separate">',
                '<div data-page-component-view="test-view"></div>',
                '</div>'
            ].join(''));

            const manager = new ComponentManager($('#container'));
            const elements = manager._collectElements();
            const $element = $(elements[0]);

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

            const manager = new ComponentManager($('#container'));
            const elements = manager._collectElements();
            const $element = $(elements[0]);

            expect(elements.length).toEqual(1);
            expect($element.data()).toEqual({
                pageComponentModule: 'oroui/js/app/components/jquery-widget-component',
                pageComponentOptions: {
                    printPage: '',
                    widgetModule: 'orofrontend/default/js/widgets/print-page-widget'
                }
            });
        });

        describe('required sibling components', function() {
            describe('simple dependency', function() {
                let manager;
                beforeEach(async function() {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-c" ' +
                            'data-page-component-module="js/needs-a-component"></div>',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/bar-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    return manager.init();
                });

                it('reference is established', function() {
                    expect(manager.get('component-c').componentA).toBeDefined();
                });

                it('when sibling is disposed, then reference is undefined', function() {
                    manager.get('component-a').dispose();
                    expect(manager.get('component-c').componentA).toBeUndefined();
                });
            });

            describe('override required componentName with options', function() {
                let manager;
                beforeEach(async function() {
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
                    return manager.init();
                });

                it('compare components', function() {
                    expect(manager.get('component-c').componentA).toBe(manager.get('component-e'));
                });
            });

            describe('remove dependency over component extend', function() {
                let manager;
                beforeEach(async function() {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-d" ' +
                            'data-page-component-module="js/extend-no-need-a-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    return manager.init();
                });

                it('compare components', function() {
                    expect(manager.get('component-d').componentA).toBeUndefined();
                });
            });

            describe('complex dependencies', function() {
                let manager;
                beforeEach(async function() {
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
                    return manager.init();
                });

                it('compare components', function() {
                    expect(manager.get('component-a').componentB).toBe(manager.get('component-b'));
                    expect(manager.get('component-b').componentC).toBe(manager.get('component-c'));
                    expect(manager.get('component-b').componentE).toBe(manager.get('component-e'));
                });
            });

            describe('missing required sibling component', function() {
                let manager;
                beforeEach(async function() {
                    window.setFixtures([
                        '<div id="container" data-layout="separate">',
                        '<div data-page-component-name="component-a" ' +
                            'data-page-component-module="js/needs-b-component"></div>',
                        '</div>'
                    ].join(''));
                    manager = new ComponentManager($('#container'));
                    return manager.init();
                });

                it('has to be undefined', function() {
                    expect(manager.get('component-a').componentB).toBeUndefined();
                });
            });

            describe('options parameter is not able to remove dependency', function() {
                let manager;
                beforeEach(async function() {
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
                    return manager.init();
                });

                it('reference on sibling component nevertheless established', function() {
                    expect(manager.get('component-a').componentB).toBe(manager.get('component-b'));
                });
            });

            describe('circular dependency', function() {
                let manager;
                beforeEach(async function() {
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
                    return manager.init();
                });

                it('check error', function() {
                    expect(manager._handleError).toHaveBeenCalled();
                    expect(manager._handleError.calls.mostRecent().args[1].message).toContain('circular dependency');
                });
            });
        });

        describe('delays component\'s initialization until UI event,', () => {
            let manager;
            beforeEach(async function() {
                window.setFixtures(`
                    <div id="container" data-layout="separate">
                        <div id="init-on" data-page-component-init-on="click">
                            <div data-page-component-name="component-foo"
                                data-page-component-module="js/foo-component"></div>
                        </div>
                    </div>
                `);

                manager = new ComponentManager($('#container'));
                return manager.init();
            });

            it('component initially not initialized', () => {
                expect(manager.get('component-foo')).toBeNull();
            });

            describe('after click event', () => {
                beforeEach(async function() {
                    $('#init-on').click();
                    return $.when(...Object.values(manager.initPromises).map(({promise}) => promise));
                });

                it('component gets initialized', () => {
                    expect(manager.get('component-foo')).not.toBeNull();
                });
            });
        });

        describe('`init-on-asap` component within `init-on` element', () => {
            let manager;
            beforeEach(async function() {
                window.setFixtures(`
                    <div id="container" data-layout="separate">
                        <div id="init-on" data-page-component-init-on="click">
                            <div
                                data-page-component-init-on="asap"
                                data-page-component-name="component-bar" 
                                data-page-component-module="js/bar-component"></div>
                        </div>
                    </div>
                `);

                manager = new ComponentManager($('#container'));
                return manager.init();
            });

            it('initially initialized', () => {
                expect(manager.get('component-bar')).not.toBeNull();
            });
        });

        describe('`init-on` rule applies only on component within same layout,', () => {
            let managerA;
            let managerB;
            beforeEach(async function() {
                window.setFixtures(`
                    <div id="container-a" data-layout="separate">
                        <div id="init-on" data-page-component-init-on="click">
                            <div
                                data-page-component-name="component-bar" 
                                data-page-component-module="js/bar-component"></div>
                            <div id="container-b" data-layout="separate">
                                <div
                                    data-page-component-name="component-foo" 
                                    data-page-component-module="js/bar-component"></div>
                            </div>
                        </div>
                    </div>
                `);

                managerA = new ComponentManager($('#container-a'));
                managerB = new ComponentManager($('#container-b'));
                return $.when(
                    managerA.init(),
                    managerB.init()
                );
            });

            it('component im outer layout is not initially initialized`', () => {
                expect(managerA.get('component-bar')).toBeNull();
            });

            it('component in nested layout is initially initialized', () => {
                expect(managerB.get('component-foo')).not.toBeNull();
            });
        });

        describe('delegated `init-on` event handler to third party element`', () => {
            let manager;
            beforeEach(async function() {
                window.setFixtures(`
                    <div id="container" data-layout="separate">
                        <div id="init-on"></div>
                        <div
                            data-page-component-init-on="click #init-on"
                            data-page-component-name="component-bar" 
                            data-page-component-module="js/bar-component"></div>
                    </div>
                `);

                manager = new ComponentManager($('#container'));
                return manager.init();
            });

            it('component initially not initialized', () => {
                expect(manager.get('component-bar')).toBeNull();
            });

            describe('after click event', () => {
                beforeEach(async function() {
                    $('#init-on').click();
                    return $.when(...Object.values(manager.initPromises).map(({promise}) => promise));
                });

                it('component gets initialized', () => {
                    expect(manager.get('component-bar')).not.toBeNull();
                });
            });
        });
    });
});
