define(function(Chaplin) {
    'use strict';

    const _ = require('underscore');
    const BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @class ColumnManagerComponent
     * @extends BaseComponent
     */
    const ColumnRendererComponent = BaseComponent.extend({
        /**
         * Full collection of columns
         * @type {Backgrid.Columns}
         */
        columns: null,

        /**
         * @inheritdoc
         */
        constructor: function ColumnRendererComponent(options) {
            ColumnRendererComponent.__super__.constructor.call(this, options);
        },

        /**
         * @inheritdoc
         */
        initialize: function(options) {
            ColumnRendererComponent.__super__.initialize.call(this, options);
        },

        /**
         * @inheritdoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            ColumnRendererComponent.__super__.dispose.call(this);
        },

        getHtml: function($element) {
            return $element.html();
        },

        getRawAttributes: function($element, attributes) {
            attributes.class = attributes.class || '';

            if ($element.length) {
                attributes.class = this._getElementClasses($element, attributes.class);
            }
            return this._getAttributesRaw(attributes);
        },

        _getElementClasses: function($element, additionalRawClasses) {
            const elementRawClasses = $element.attr('class') || '';
            const elementClasses = _.union(additionalRawClasses.split(' '), elementRawClasses.split(' '));

            return _.without(elementClasses, '');
        },

        _getAttributesRaw: function(attributes) {
            let raw = '';
            _.each(attributes, function(value, name) {
                raw += ' ' + name + '="' + (_.isArray(value) ? value.join(' ') : value) + '"';
            });
            return raw.trim();
        }
    });

    return ColumnRendererComponent;
});
