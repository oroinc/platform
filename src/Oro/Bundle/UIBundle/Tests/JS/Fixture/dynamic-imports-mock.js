module.exports = {
    'js/module-a': () => new Promise(resolve => {
        setTimeout(() => resolve({'default': {moduleName: 'a'}, '__esModule': true}));
    }),
    'js/module-b': () => new Promise(resolve => {
        setTimeout(() => resolve({'default': {moduleName: 'b'}, '__esModule': true}));
    }),
    'js/module-c': () => new Promise(resolve => {
        setTimeout(() => resolve({'default': {moduleName: 'c'}, '__esModule': true}));
    })
};
