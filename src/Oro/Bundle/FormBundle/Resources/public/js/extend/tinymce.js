const _ = require('underscore');
const tinyMCE = require('components/tinymce/tinymce');
const moduleConfig = require('module-config').default(module.id);

require.resolveWeak(// fixes issue with missing icons/default/icons.js file in public dir
    '!file-loader?name=[path]icons.[ext]&outputPath=../!bundles/components/tinymce/icons/default/icons.min.js'
);

_.extend(tinyMCE, _.pick(moduleConfig, 'baseURL'));

module.exports = tinyMCE;
