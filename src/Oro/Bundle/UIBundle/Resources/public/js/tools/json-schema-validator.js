import _ from 'underscore';

function isPlainObject(obj) {
    return typeof obj === 'object' && obj !== null && obj.constructor === Object;
}

/**
 * Simplified validator for data structure
 * based on idea of http://json-schema.org/
 * partially supports https://spacetelescope.github.io/understanding-json-schema/reference/index.html
 */
export default {
    /**
     * Validates data against schema
     *
     * @param {Object} schema
     * @param {*} value
     *
     * @return {boolean}
     */
    validate(schema, value) {
        switch (schema.type) {
            case 'object': // "dependencies" and "patternProperties" are not supported
                return isPlainObject(value) &&
                    this.checkMinMaxProperties(schema, value) &&
                    this.checkRequiredProperties(schema, value) &&
                    this.checkProperties(schema, value) &&
                    this.checkAdditionalProperties(schema, value);

            case 'array':
                return Array.isArray(value) &&
                    this.checkMinItems(schema, value) &&
                    this.checkMaxItems(schema, value) &&
                    this.checkUniqueItems(schema, value) &&
                    this.checkItemsSchema(schema, value) &&
                    this.checkItemsTuple(schema, value);

            case 'string': // "format" is not supported
                return _.isString(value) &&
                    this.checkMinLength(schema, value) &&
                    this.checkMaxLength(schema, value) &&
                    this.checkEnum(schema, value) &&
                    this.checkPattern(schema, value);

            case 'integer': // "multipleOf" is not supported
                return _.isNumber(value) &&
                    parseInt(value) === value &&
                    this.checkMinimum(schema, value) &&
                    this.checkMaximum(schema, value) &&
                    this.checkEnum(schema, value);

            case 'number': // "multipleOf" is not supported
                return _.isNumber(value) &&
                    this.checkMinimum(schema, value) &&
                    this.checkMaximum(schema, value) &&
                    this.checkEnum(schema, value);

            case 'boolean':
                return _.isBoolean(value) &&
                    this.checkEnum(schema, value);

            case 'null':
                return value === null;

            default:
                if (Array.isArray(schema.type)) {
                    return this.checkAnyOfType(schema, value);
                } else if (!schema.type) {
                    return this.checkEnum(schema, value);
                }
                throw new Error('Can not validate JSON of unknown or incorrect type');
        }
    },

    /**
     * Checks if the value within enum (in case enum is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkEnum(schema, value) {
        return !schema.enum || schema.enum.indexOf(value) !== -1;
    },

    /**
     * Checks if the value has greater or equal to minLength (in case minLength is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkMinLength(schema, value) {
        return !('minLength' in schema) || value.length >= schema.minLength;
    },

    /**
     * Checks if the value has less or equal to maxLength (in case maxLength is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkMaxLength(schema, value) {
        return !('maxLength' in schema) || value.length <= schema.maxLength;
    },

    /**
     * Checks if the value matches the pattern (in case a pattern is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkPattern(schema, value) {
        return !schema.pattern || (new RegExp(schema.pattern)).test(value);
    },

    /**
     * Checks if the value is greater or equals (in case minimum and exclusiveMinimum are defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkMinimum(schema, value) {
        return !('minimum' in schema) ||
            !schema.exclusiveMinimum && value >= schema.minimum || value > schema.minimum;
    },

    /**
     * Checks if the value is less or equals (in case maximum and exclusiveMaximum are defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @return {boolean}
     * @protected
     */
    checkMaximum(schema, value) {
        return !('maximum' in schema) ||
            !schema.exclusiveMaximum && value <= schema.maximum || value < schema.maximum;
    },

    /**
     * Checks if the value has greater or equals items of minItems (in case minItems is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkMinItems(schema, value) {
        return !('minItems' in schema) || value.length >= schema.minItems;
    },

    /**
     * Checks if the value has less or equals items of maxItems (in case maxItems is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkMaxItems(schema, value) {
        return !('maxItems' in schema) || value.length <= schema.maxItems;
    },

    /**
     * Checks if the value has unique items (in case uniqueItems is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkUniqueItems(schema, value) {
        return !schema.uniqueItems || _.uniq(value).length === value.length;
    },

    /**
     * Checks if all items of the value matches its schema (in case items is defined as pain object of JSON schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkItemsSchema(schema, value) {
        return !schema.items || !isPlainObject(schema.items) ||
            _.every(value, _.partial(this.validate.bind(this), schema.items));
    },

    /**
     * Checks if sequence of items in the value matches sequence items in schema
     * (in case items is defined as array of JSON schema objects)
     * takes in account restriction for additionalItems
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkItemsTuple(schema, value) {
        return !schema.items || !Array.isArray(schema.items) ||
            (schema.additionalItems !== false || schema.items.length >= value.length) &&
            _.every(value, (val, i) => !schema.items[i] || this.validate(schema.items[i], val));
    },

    /**
     * Checks if the value object has proper number of properties
     * (in case minProperties or/and maxProperties are defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkMinMaxProperties(schema, value) {
        let number;
        return !('minProperties' in schema || 'maxProperties' in schema) ||
            (number = _.keys(value).length) !== null &&
            (!('minProperties' in schema) || number >= schema.minProperties) &&
            (!('maxProperties' in schema) || number <= schema.maxProperties);
    },

    /**
     * Checks if all required properties are in the value object
     * (in case required is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkRequiredProperties(schema, value) {
        return !schema.required || _.difference(schema.required, _.keys(value)).length === 0;
    },

    /**
     * Checks if every property of the value object matches its schema
     * (in case properties is defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkProperties(schema, value) {
        return !schema.properties ||
            _.every(value, (val, prop) => !(prop in schema.properties) || this.validate(schema.properties[prop], val));
    },

    /**
     * Checks if additional properties of the value object are allowed
     * and if they match additionalProperties if it represents the schema
     * (in case properties and additionalProperties are defined in schema)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkAdditionalProperties(schema, value) {
        let addProps;
        return !('additionalProperties' in schema && schema.properties) ||
            (addProps = _.difference(_.keys(value), _.keys(schema.properties))) !== null &&
            (
                schema.additionalProperties === false && addProps.length === 0 ||
                isPlainObject(schema.additionalProperties) &&
                _.every(addProps, prop => this.validate(schema.additionalProperties, value[prop]))
            );
    },

    /**
     * Checks if the value matches to any of types
     * (in case type is defined as array)
     *
     * @param {Object} schema
     * @param {*} value
     * @returns {boolean}
     */
    checkAnyOfType(schema, value) {
        return !Array.isArray(schema.type) || _.any(schema.type, type => this.validate({type: type}, value));
    }
};
