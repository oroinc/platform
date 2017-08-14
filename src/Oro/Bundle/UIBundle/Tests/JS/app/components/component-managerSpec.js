define(function(require) {
    'use strict';

    require('jasmine-jquery');
    var $ = require('jquery');
    var ComponentManager = require('oroui/js/app/components/component-manager');

    require('oroui/js/app/modules/component-shortcuts-module');

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
    });
});
