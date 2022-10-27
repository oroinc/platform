define(function(require) {
    'use strict';

    const loadModulesModuleInjector = require('inject-loader!oroui/js/app/services/load-modules');
    const dynamicImportsMock = require('../../Fixture/dynamic-imports-mock');
    let loadModules;

    describe('oroui/js/app/services/load-modules', function() {
        beforeEach(function() {
            loadModules = loadModulesModuleInjector({
                'dynamic-imports': dynamicImportsMock
            });
        });

        it('handle load error', function() {
            expect(() => loadModules(['js/module-z'])).toThrow();
        });

        it('load single module as string to callback function', function(done) {
            loadModules('js/module-a', function(module) {
                expect(module).toEqual({moduleName: 'a'});
                done();
            });
        });

        it('load single module as string to promise', function(done) {
            loadModules('js/module-a').then(function(module) {
                expect(module).toEqual({moduleName: 'a'});
                done();
            });
        });

        it('load single module as array to callback function', function(done) {
            loadModules(['js/module-a'], function(module) {
                expect(module).toEqual({moduleName: 'a'});
                done();
            });
        });

        it('load single module as array to promise', function(done) {
            loadModules(['js/module-a']).then(function(module) {
                expect(module).toEqual([{moduleName: 'a'}]);
                done();
            });
        });

        it('load couple modules to callback function', function(done) {
            loadModules(['js/module-a', 'js/module-b'], function(moduleA, moduleB) {
                expect(moduleA).toEqual({moduleName: 'a'});
                expect(moduleB).toEqual({moduleName: 'b'});
                done();
            });
        });

        it('load couple modules to promise', function(done) {
            loadModules(['js/module-a', 'js/module-b']).then(function([moduleA, moduleB]) {
                expect(moduleA).toEqual({moduleName: 'a'});
                expect(moduleB).toEqual({moduleName: 'b'});
                done();
            });
        });

        it('load modules map to callback function', function(done) {
            const modules = {a: 'js/module-a', b: 'js/module-b'};
            loadModules(modules, function(obj) {
                expect(obj).toBe(modules);
                expect(obj).toEqual({a: {moduleName: 'a'}, b: {moduleName: 'b'}});
                done();
            });
        });

        it('load modules map to promise', function(done) {
            const modules = {a: 'js/module-a', b: 'js/module-b'};
            loadModules(modules).then(function(obj) {
                expect(obj).toBe(modules);
                expect(obj).toEqual({a: {moduleName: 'a'}, b: {moduleName: 'b'}});
                done();
            });
        });

        it('execute load callback with defined context', function(done) {
            const context = {};
            loadModules('js/module-a', function() {
                expect(context).toBe(this);
                done();
            }, context);
        });
    });
});
