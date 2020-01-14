module.exports = (resolver, mapConfig) => {
    const { '*': generalMap, ...customMap } = mapConfig;

    return {
        '*': generalMap || {},
        ...Object.fromEntries(Object.entries(customMap).map(([moduleName, map]) => [resolver(moduleName), map]))
    };
};
