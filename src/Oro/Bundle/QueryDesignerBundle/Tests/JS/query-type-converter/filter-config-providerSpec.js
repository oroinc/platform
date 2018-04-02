define(function(require) {
    'use strict';

    var data = JSON.parse(require('text!../Fixture/query-type-converter/filter-config-data.json'));
    var FilterConfigProvider = require('oroquerydesigner/js/query-type-converter/filter-config-provider');
    var exposure = require('requirejs-exposure')
        .disclose('oroquerydesigner/js/query-type-converter/filter-config-provider');

    describe('oroquerydesigner/js/query-type-converter/filter-config-provider', function() {
        var filterConfigProvider;
        var enumFilterInitializerMock;

        beforeEach(function(done) {
            enumFilterInitializerMock = jasmine.createSpy('enumFilterInitializer')
                .and.callFake(function(config, object) {
                    config.foo = object.relatedEntityName;
                });
            spyOn(exposure.retrieve('tools'), 'loadModules').and
                .callFake(function(modules, callback, context) {
                    modules[data.filters[0].init_module] = enumFilterInitializerMock;
                    return $.when(modules).then(callback.bind(context));
                });

            filterConfigProvider = new FilterConfigProvider(data);
            filterConfigProvider.loadInitModules().done(done);
        });

        it('Check getApplicableFilterConfig without init_module', function() {
            var signature = {
                entity: 'Oro',
                field: 'id',
                type: 'integer'
            };

            expect(filterConfigProvider.getApplicableFilterConfig(signature)).toEqual(data.filters[1]);
        });

        it('Check getApplicableFilterConfig with init_module and config resolver method', function() {
            var signature = {
                entity: 'Oro',
                field: 'status',
                relatedEntityName: 'Oro',
                type: 'enum'
            };
            var actualConfig = filterConfigProvider.getApplicableFilterConfig(signature);
            var expectedConfig = jasmine.objectContaining(data.filters[0]);

            expect(enumFilterInitializerMock).toHaveBeenCalledWith(expectedConfig, signature);
            expect(actualConfig).toEqual(expectedConfig);
            expect(actualConfig).toEqual(jasmine.objectContaining({
                foo: signature.relatedEntityName
            }));
            expect(actualConfig).not.toBe(data.filters[0]);
        });

        it('Check getApplicableFilterConfig without arguments', function() {
            expect(function() {
                filterConfigProvider.getApplicableFilterConfig();
            }).toThrowError(TypeError);
        });

        it('Check getFilterConfigsByType', function() {
            expect(filterConfigProvider.getFilterConfigsByType('number')).toEqual([data.filters[1], data.filters[2]]);
        });
    });
});
