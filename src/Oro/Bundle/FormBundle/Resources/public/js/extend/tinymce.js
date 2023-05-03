const _ = require('underscore');
const tinyMCE = require('tinymce');
require.context(
    '!file-loader?name=[path][name].[ext]&outputPath=../_static/&context=tinymce!tinymce/icons',
    true,
    /.*/
);
require.context(
    '!file-loader?name=[path][name].[ext]&outputPath=../_static/&context=tinymce!tinymce/models',
    true,
    /.*/
);
require.context(
    '!file-loader?name=[path][name].[ext]&outputPath=../_static/&context=tinymce!tinymce/plugins',
    true,
    /.*/
);
require.context(
    '!file-loader?name=[path][name].[ext]&outputPath=../_static/&context=tinymce!tinymce/skins',
    true,
    /.*/
);
require.context(
    '!file-loader?name=[path][name].[ext]&outputPath=../_static/&context=tinymce!tinymce/themes',
    true,
    /.*/
);
const moduleConfig = require('module-config').default(module.id);

_.extend(tinyMCE, _.pick(moduleConfig, 'baseURL'));

module.exports = tinyMCE;
