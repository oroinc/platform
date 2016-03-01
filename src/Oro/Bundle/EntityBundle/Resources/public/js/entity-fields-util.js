define(function(require) {
    'use strict';

    var _ = require('underscore');
    var __ = require('orotranslation/js/translator');
    var mediator = require('oroui/js/mediator');
    var EntityError = require('./entity-error');

    function Util(entity, data) {
        this.init(entity, data);
    }

    /**
     * Filters passed fields by exclude mask
     *
     * @param {Array} fields
     * @param {Object} exclude
     * @returns {Object}
     * @static
     */
    Util.filterFields = function(fields, exclude) {
        fields = _.filter(fields, function(item) {
            var result;
            result = !_.some(exclude, function(rule) {
                var result;
                var cut;
                // exclude can be a property name
                if (_.isString(rule)) {
                    result = _.intersection(
                        [rule],
                        _.keys(item)
                    ).length > 0;
                } else {
                    // or exclude can be an object with data to compare
                    cut = _.pick(item, _.keys(rule));
                    result = _.isEqual(cut, rule);
                }

                return result;
            });
            return result;
        });
        return fields;
    };

    Util.errorHandler = (function() {
        var message = __('oro.entity.not_exist');
        var handler = _.bind(mediator.execute, mediator, 'showErrorMessage', message);
        return _.throttle(handler, 100, {trailing: false});
    }());

    Util.prototype = {

        init: function(entity, data) {
            this.entity = entity;
            this.data = data || {};
        },

        /**
         * Parses path-string and returns array of objects
         *
         * Field Path:
         *      account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName
         * Returns Chain:
         *  [{
         *      entity: {Object},
         *      path: "",
         *      basePath: ""
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account",
         *      basePath: "account+OroCRM\[...]\Account"
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account+OroCRM\[...]\Account::contacts",
         *      basePath: "account+OroCRM\[...]\Account::contacts+OroCRM\[...]Contact"
         *  }, {
         *      field: {Object},
         *      path: "account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName"
         *  }]
         *
         * @param {string} path
         * @param {boolean?} trim - flag, allows to omit last item if it's a field
         * @returns {Array.<Object>}
         */
        pathToEntityChain: function(path, trim) {
            var self = this;
            var data = this.data;

            if (!data[this.entity]) {
                return [];
            }

            var chain = [{
                entity: data[this.entity],
                path: '',
                basePath: ''
            }];

            if (!path) {
                return this.entity ? chain : [];
            }

            try {
                _.each(path.split('+'), function(item, i) {
                    var fieldName;
                    var entityName;
                    var pos;

                    if (i === 0) {
                        // first item is always just a field name
                        fieldName = item;
                    } else {
                        pos = item.indexOf('::');
                        if (pos !== -1) {
                            entityName = item.slice(0, pos);
                            fieldName = item.slice(pos + 2);
                        } else {
                            entityName = item;
                        }
                    }

                    if (entityName) {
                        // set entity for previous chain part
                        chain[i].entity = data[entityName];
                    }

                    if (fieldName) {
                        item = {
                            // take field from entity of previous chain part
                            field: chain[i].entity.fieldsIndex[fieldName]
                        };
                        chain.push(item);
                        item.path = self.entityChainToPath(chain);
                        if (item.field.related_entity) {
                            item.basePath = item.path + '+' + item.field.related_entity.name;
                        }
                    }
                });
            } catch (e) {
                Util.errorHandler();
                throw new EntityError('Can not build entity chain by given path "' + path + '"');
            }

            // if last item in the chain is a field -- cut it off
            if (trim && chain[chain.length - 1].entity === void 0) {
                chain = chain.slice(0, -1);
            }

            return chain;
        },

        /**
         * Combines path-string from array of objects
         *
         * Chain:
         *  [{
         *      entity: {Object},
         *      path: "",
         *      basePath: ""
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account",
         *      basePath: "account+OroCRM\[...]\Account"
         *  }, {
         *      entity: {Object},
         *      field: {Object},
         *      path: "account+OroCRM\[...]\Account::contacts",
         *      basePath: "account+OroCRM\[...]\Account::contacts+OroCRM\[...]Contact"
         *  }, {
         *      field: {Object},
         *      path: "account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName"
         *  }]
         *
         *  Returns Field Path:
         *      account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName
         *
         * @param {Array.<Object>} chain
         * @param {number=} end - number of chain-items which need to be ignored
         * @returns {string}
         */
        entityChainToPath: function(chain, end) {
            var path;
            end = end || chain.length;

            try {
                chain = _.map(chain.slice(1, end), function(item) {
                    var result = item.field.name;
                    if (item.entity) {
                        result += '+' + item.entity.name;
                    }
                    return result;
                });
            } catch (e) {
                Util.errorHandler();
                chain = [];
            }

            path = chain.join('::');

            return path;
        },

        /**
         * Prepares the object with field's info which can be matched for conditions
         *
         * @param {string} fieldId - Field Path, such as
         *      account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName
         * @returns {Object}
         */
        getApplicableConditions: function(fieldId) {
            var result = {};
            var chain;
            var entity;

            if (!fieldId) {
                return result;
            }

            try {
                chain = this.pathToEntityChain(fieldId);
            } catch (e) {
                return result;
            }

            entity = chain[chain.length - 1];
            if (entity) {
                result = {
                    parent_entity: null,
                    entity: entity.field.entity.name,
                    field:  entity.field.name
                };
                if (chain.length > 2) {
                    result.parent_entity = chain[chain.length - 2].field.entity.name;
                }
                _.extend(result, _.pick(entity.field, ['type', 'identifier']));
            }

            return result;
        },

        /**
         * Converts Field Path to Property Path
         *
         * Field Path:
         *      account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName
         * Returns Property Path:
         *      account.contacts.firstName
         *
         * @param {string} path
         * @returns {string}
         */
        getPropertyPathByPath: function(path) {
            var propertyPathParts = [];
            _.each(path.split('+'), function(item, i) {
                var part;
                if (i === 0) {
                    // first item is always just a field name
                    propertyPathParts.push(item);
                } else {
                    // field name can contain '::'
                    // thus cut off entity name with first entrance '::',
                    // remaining part is a field name
                    part = item.split('::').slice(1).join('::');
                    if (part) {
                        propertyPathParts.push(part);
                    }
                }
            });

            return propertyPathParts.join('.');
        },

        /**
         * Converts Property Path to Field Path
         *
         * Property Path:
         *      account.contacts.firstName
         * Returns Field Path:
         *      account+OroCRM\[...]\Account::contacts+OroCRM\[...]\Contact::firstName
         *
         * @param {string} pathData
         * @returns {string}
         */
        getPathByPropertyPath: function(pathData) {
            var fieldIdParts;
            if (!_.isArray(pathData)) {
                pathData = pathData.split('.');
            }

            var entityData = this.data[this.entity];
            try {
                fieldIdParts = _.map(pathData.slice(0, pathData.length - 1), function(fieldName) {
                    var fieldPartId = fieldName;
                    var fieldsData = null;
                    if (entityData.hasOwnProperty('fieldsIndex')) {
                        fieldsData = entityData.fieldsIndex;
                    } else if (
                        entityData.hasOwnProperty('related_entity') &&
                            entityData.related_entity.hasOwnProperty('fieldsIndex')
                    ) {
                        fieldsData = entityData.related_entity.fieldsIndex;
                    }

                    if (fieldsData && fieldsData.hasOwnProperty(fieldName)) {
                        entityData = fieldsData[fieldName];

                        if (entityData.hasOwnProperty('related_entity')) {
                            fieldPartId += '+' + entityData.related_entity.name;
                        }
                    }

                    return fieldPartId;
                });

                fieldIdParts.push(pathData[pathData.length - 1]);
            } catch (e) {
                Util.errorHandler();
                throw new EntityError('Can not define entity path by given property path "' + pathData + '"');
            }
            return fieldIdParts.join('::');
        }
    };

    return Util;
});
