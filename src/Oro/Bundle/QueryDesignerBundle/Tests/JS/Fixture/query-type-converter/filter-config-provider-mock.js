import $ from 'jquery';
import FilterConfigProvider from 'oroquerydesigner/js/query-type-converter/filter-config-provider';
import data from './filter-config-data.json';

const enumFilterInitializerMock = jasmine.createSpy('enumFilterInitializer')
    .and.callFake((config, object) => {
        config.foo = object.relatedEntityName;
    });
const loadModulesMock = jasmine.createSpy('loadModules').and
    .callFake(modules => {
        modules[data.filters[0].init_module] = enumFilterInitializerMock;
        return $.when(modules);
    });

export default class extends FilterConfigProvider {
    loadModules(modules) {
        return loadModulesMock(Object.fromEntries(modules.map(name => [name, name])))
            .then(modules => {
                this.filterModules = modules;
            });
    }
}

export {
    enumFilterInitializerMock
};
