var _ = require('underscore');

module.exports = function(source) {
    this.cacheable && this.cacheable();
    var template = _.template(source, null, this.tplSettings);
    return 'var _ = require(\'underscore\');\nmodule.exports = ' + template.source;
};
