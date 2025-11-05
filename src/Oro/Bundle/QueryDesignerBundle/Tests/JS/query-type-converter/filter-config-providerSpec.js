import data from '../Fixture/query-type-converter/filter-config-data.json';
import FilterConfigProvider, {enumFilterInitializerMock}
    from '../Fixture/query-type-converter/filter-config-provider-mock.js';

describe('oroquerydesigner/js/query-type-converter/filter-config-provider', () => {
    let filterConfigProvider;

    beforeEach(done => {
        filterConfigProvider = new FilterConfigProvider(data);
        filterConfigProvider.loadInitModules().done(done);
    });

    it('Check getApplicableFilterConfig without init_module', () => {
        const signature = {
            entity: 'Oro',
            field: 'id',
            type: 'integer'
        };

        expect(filterConfigProvider.getApplicableFilterConfig(signature)).toEqual(data.filters[1]);
    });

    it('Check getApplicableFilterConfig with init_module and config resolver method', () => {
        const signature = {
            entity: 'Oro',
            field: 'status',
            relatedEntityName: 'Oro',
            type: 'enum'
        };
        const actualConfig = filterConfigProvider.getApplicableFilterConfig(signature);
        const expectedConfig = jasmine.objectContaining(data.filters[0]);

        expect(enumFilterInitializerMock).toHaveBeenCalledWith(expectedConfig, signature);
        expect(actualConfig).toEqual(expectedConfig);
        expect(actualConfig).toEqual(jasmine.objectContaining({
            foo: signature.relatedEntityName
        }));
        expect(actualConfig).not.toBe(data.filters[0]);
    });

    it('Check getApplicableFilterConfig without arguments', () => {
        expect(() => {
            filterConfigProvider.getApplicableFilterConfig();
        }).toThrowError(TypeError);
    });

    it('Check getFilterConfigsByType', () => {
        expect(filterConfigProvider.getFilterConfigsByType('number')).toEqual([data.filters[1], data.filters[2]]);
    });
});
