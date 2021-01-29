const _ = require('underscore');

module.exports = function(source) {
    this.cacheable && this.cacheable();
    const template = _.template(source, null, this.tplSettings);
    return `var _ = require('underscore');\nmodule.exports = ${template.source}`;
};
