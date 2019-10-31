/* eslint-env node */
const path = require('path');

module.exports = {
    sourceType: 'unambiguous',
    presets: [
        [
            path.resolve(__dirname, './node_modules/@babel/preset-env'), {
                useBuiltIns: 'usage',
                corejs: {
                    version: 3,
                    proposals: true
                }
            }
        ]
    ],
    plugins: [
        path.resolve(__dirname, './node_modules/@babel/plugin-transform-runtime')
    ]
};
