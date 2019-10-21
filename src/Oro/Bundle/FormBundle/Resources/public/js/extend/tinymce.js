const _ = require('underscore');
const tinyMCE = require('components/tinymce/tinymce');
const moduleConfig = require('module-config').default(module.id);

_.extend(tinyMCE, _.pick(moduleConfig, 'baseURL'));

module.exports = tinyMCE;
