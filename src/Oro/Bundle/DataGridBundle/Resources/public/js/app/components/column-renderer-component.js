define(function(Chaplin) {
    'use strict';

    var ColumnRendererComponent;
    var _ = require('underscore');
    var BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * @class ColumnManagerComponent
     * @extends BaseComponent
     */
    ColumnRendererComponent = BaseComponent.extend({
        /**
         * Full collection of columns
         * @type {Backgrid.Columns}
         */
        columns: null,

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            ColumnRendererComponent.__super__.initialize.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        dispose: function() {
            if (this.disposed) {
                return;
            }

            ColumnRendererComponent.__super__.dispose.apply(this, arguments);
        },

        getHtml: function($element) {
            return $element.html();
        },

        getRawAttributes: function($element, attributes) {
            attributes.class = attributes.class || '';

            if ($element.length) {
                attributes.class = this._getElementClasses($element, attributes.class)
            }
            return this._getAttributesRaw(attributes);
        },

        _getElementClasses: function($element, additionalRawClasses) {
            var elementRawClasses = $element.attr('class') || '';
            var elementClasses = _.union(additionalRawClasses.split(' '), elementRawClasses.split(' '));

            return _.without(elementClasses, '');
        },

        _getAttributesRaw: function(attributes) {
            var raw = '';
            _.each(attributes, function(value, name) {
                raw += ' ' + name + '=' + value;
            });
            return raw.trim();
        }
    });

    return ColumnRendererComponent;
});
