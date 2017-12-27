define(function(require) {
    'use strict';

    var tools = require('oroui/js/tools');
    var requirejsExposure = require('requirejs-exposure');
    var exposure = requirejsExposure.disclose('oroui/js/tools');
    var requireMock = require('./Fixture/requirejs-mock');

    describe('oroui/js/tools', function() {
        describe('loadModules method', function() {
            beforeEach(function() {
                exposure.substitute('require').by(requireMock);
            });

            afterEach(function() {
                exposure.recover('require');
            });

            it('handle load error', function(done) {
                tools.loadModules(['js/module-z'], function() {}).fail(function(e) {
                    expect(e).toEqual(jasmine.any(Error));
                    done();
                });
            });

            it('load single module as string to callback function', function(done) {
                tools.loadModules('js/module-a', function(module) {
                    expect(module).toEqual({moduleName: 'a'});
                    done();
                });
            });

            it('load single module as string to promise', function(done) {
                tools.loadModules('js/module-a').then(function(module) {
                    expect(module).toEqual({moduleName: 'a'});
                    done();
                });
            });

            it('load single module as array to callback function', function(done) {
                tools.loadModules(['js/module-a'], function(module) {
                    expect(module).toEqual({moduleName: 'a'});
                    done();
                });
            });

            it('load single module as array to promise', function(done) {
                tools.loadModules(['js/module-a']).then(function(module) {
                    expect(module).toEqual({moduleName: 'a'});
                    done();
                });
            });

            it('load couple modules to callback function', function(done) {
                tools.loadModules(['js/module-a', 'js/module-b'], function(moduleA, moduleB) {
                    expect(moduleA).toEqual({moduleName: 'a'});
                    expect(moduleB).toEqual({moduleName: 'b'});
                    done();
                });
            });

            it('load couple modules to promise', function(done) {
                tools.loadModules(['js/module-a', 'js/module-b']).then(function(moduleA, moduleB) {
                    expect(moduleA).toEqual({moduleName: 'a'});
                    expect(moduleB).toEqual({moduleName: 'b'});
                    done();
                });
            });

            it('load modules map to callback function', function(done) {
                var modules = {a: 'js/module-a', b: 'js/module-b'};
                tools.loadModules(modules, function(obj) {
                    expect(obj).toBe(modules);
                    expect(obj).toEqual({a: {moduleName: 'a'}, b: {moduleName: 'b'}});
                    done();
                });
            });

            it('load modules map to promise', function(done) {
                var modules = {a: 'js/module-a', b: 'js/module-b'};
                tools.loadModules(modules).then(function(obj) {
                    expect(obj).toBe(modules);
                    expect(obj).toEqual({a: {moduleName: 'a'}, b: {moduleName: 'b'}});
                    done();
                });
            });

            it('execute load callback with defined context', function(done) {
                var context = {};
                tools.loadModules('js/module-a', function() {
                    expect(context).toBe(this);
                    done();
                }, context);
            });
        });
    });
});
