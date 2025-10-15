import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import BaseComponent from 'oroui/js/app/components/base/component';
import mediator from 'oroui/js/mediator';
import widgetManager from 'oroui/js/widget-manager';

const DeactivateFormWidgetComponent = BaseComponent.extend({
    /**
     * @property {Object}
     */
    options: {
        _wid: '',
        success: false,
        deactivated: null,
        selectors: {
            form: null
        },
        buttonName: 'activate',
        error: null
    },

    /**
     * @inheritdoc
     */
    constructor: function DeactivateFormWidgetComponent(options) {
        DeactivateFormWidgetComponent.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize: function(options) {
        this.options = _.defaults(options || {}, this.options);

        widgetManager.getWidgetInstance(
            this.options._wid,
            widget => {
                if (!this.options.success) {
                    if (this.options.error) {
                        mediator.execute('showMessage', 'error', this.options.error);
                    }

                    widget.getAction(this.options.buttonName, 'adopted', action => {
                        action.on('click', this.onClick.bind(this));
                    });
                } else {
                    mediator.trigger('widget_success:' + widget.getAlias());
                    mediator.trigger('widget_success:' + widget.getWid());

                    let response = {message: __('oro.workflow.activated')};

                    if (!_.isEmpty(this.options.deactivated)) {
                        response = _.extend(response, {
                            deactivatedMessage: __('oro.workflow.deactivated_list') +
                                _.escape(this.options.deactivated)
                        });
                    }

                    widget.trigger('formSave', response);
                    widget.remove();
                }
            }
        );
    },

    onClick: function() {
        this.options._sourceElement.find(this.options.selectors.form).trigger('submit');
    }
});

export default DeactivateFormWidgetComponent;
