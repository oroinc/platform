import routing from 'routing';
import moduleConfig from 'module-config';
const config = moduleConfig(module.id);

try {
    const response = await fetch(config.routesResource);
    const routes = await response.json();
    const options = Object.assign({debug: false, data: {}}, config);
    if (!options.debug) {
        // processed correctly only in case when routing comes via controller
        Object.assign(routes, options.data);
    }
    routing.setRoutingData(routes);
} catch (error) {
    throw new Error(`Unable to load routes from "${config.routesResource}"`);
}

export default routing;
