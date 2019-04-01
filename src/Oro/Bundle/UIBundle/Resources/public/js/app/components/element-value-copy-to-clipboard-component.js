define(function(require) {
    'use strict';

    var ElementValueCopyToClipboardComponent;
    var _ = require('underscore');
    var $ = require('jquery');
    var ViewComponent = require('oroui/js/app/components/view-component');
    var ElementValueCopyToClipboardView = require('oroui/js/app/views/element-value-copy-to-clipboard-view');

    ElementValueCopyToClipboardComponent = ViewComponent.extend({
        /**
         * @inheritDoc
         */
        constructor: function ElementValueCopyToClipboardComponent() {
            ElementValueCopyToClipboardComponent.__super__.constructor.apply(this, arguments);
        },

        /**
         * @inheritDoc
         */
        initialize: function(options) {
            var subPromises = _.values(options._subPromises);
            var viewOptions = _.defaults(
                _.omit(options, '_sourceElement', '_subPromises'),
                {el: options._sourceElement}
            );

            this._deferredInit();

            if (subPromises.length) {
                // ensure that all nested components are already initialized
                $.when.apply($, subPromises).then(function() {
                    this._initializeView(viewOptions, ElementValueCopyToClipboardView);
                });
            } else {
                this._initializeView(viewOptions, ElementValueCopyToClipboardView);
            }
        }
    });

    return ElementValueCopyToClipboardComponent;
});
