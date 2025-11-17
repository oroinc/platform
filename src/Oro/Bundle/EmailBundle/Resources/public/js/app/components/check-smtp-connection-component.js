import $ from 'jquery';
import _ from 'underscore';
import BaseComponent from 'oroui/js/app/components/base/component';
import CheckSmtpConnectionView from '../views/check-smtp-connection-view';
import CheckSavedSmtpConnectionView from '../views/check-saved-smtp-connection-view';
import CheckSmtpConnectionModel from '../models/check-smtp-connection-model';

const CheckSmtpConnectionComponent = BaseComponent.extend({
    /**
     * @inheritdoc
     */
    constructor: function CheckSmtpConnectionComponent(options) {
        CheckSmtpConnectionComponent.__super__.constructor.call(this, options);
    },

    /**
     * Initialize component
     *
     * @param {Object} options
     * @param {string} options.elementNamePrototype
     */
    initialize: function(options) {
        if (options.elementNamePrototype) {
            const viewOptions = _.extend({
                el: $(options._sourceElement).closest(options.parentElementSelector),
                entity: options.forEntity || 'user',
                entityId: options.id,
                organization: options.organization || ''
            }, options.viewOptions || {});

            if (options.view !== 'saved') {
                viewOptions.model = new CheckSmtpConnectionModel({});
                this.view = new CheckSmtpConnectionView(viewOptions);
            } else {
                this.view = new CheckSavedSmtpConnectionView(viewOptions);
            }
        } else {
            // unable to initialize
            $(options._sourceElement).remove();
        }
    }
});
export default CheckSmtpConnectionComponent;
