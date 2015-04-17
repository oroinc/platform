/*global define*/
/** @exports HiddenInitializationComponent */
define(function (require) {
    'use strict';

    var HiddenInitializationComponent,
        mediator = require('oroui/js/mediator'),
        BaseComponent = require('oroui/js/app/components/base/component');

    /**
     * Component allows hide part of DOM tree till all page components will be initialized
     *
     * Usage sample:
     *
     * > Please note that all div's attributes are required for valid work.
     *
     * ```html
     * <div class="invisible"
     *         data-page-component-module="oroui/js/app/components/hidden-initialization-component"
     *         data-layout="separate">
     *     <!-- write anything here -->
     * </div>
     * ```
     *
     * @class HiddenInitializationComponent
     * @augments BaseComponent
     */
    HiddenInitializationComponent = BaseComponent.extend(/** @lends HiddenInitializationComponent.prototype */{
        /**
         * @constructor
         * @inheritDoc
         */
        initialize: function (options) {
            this.element = options._sourceElement;
            if (!this.element) {
                return;
            }

            this.element.addClass('invisible');

            this._deferredInit();

            mediator.execute('layout:init', this.element, this).done(_.bind(function () {
                this.element.removeClass('invisible');
                this._resolveDeferredInit();
            }, this));
        }
    });

    return HiddenInitializationComponent;
});
