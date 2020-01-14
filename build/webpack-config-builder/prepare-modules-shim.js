module.exports = (resolver, config) => {
    return Object.entries(config)
        .map(([moduleName, shim]) => {
            let {imports, exports, expose} = shim;
            let uses = [];

            // convert to expose-loader? syntax
            if (expose) {
                expose = Array.isArray(expose) ? expose : [expose];
                uses.push(...expose.map(name => `expose-loader?${name}`));
            }

            // convert to imports-loader? syntax
            if (imports && imports.length) {
                uses.push(`imports-loader?${imports.join(',')}`);
            }

            // convert to exports-loader? syntax
            if (exports) {
                uses.push(`exports-loader?${exports}`);
            }

            return {
                test: resolver(moduleName),
                use: uses
            }
        });
};
