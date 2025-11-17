import $ from 'jquery';
import BaseComponent from 'oroui/js/app/components/base/component';

const MicrosoftSyncCheckbox = BaseComponent.extend({
    /**
     * @inheritDoc
     */
    constructor: function MicrosoftSyncCheckbox(options) {
        MicrosoftSyncCheckbox.__super__.constructor.call(this, options);
    },

    /**
     * @inheritDoc
     */
    initialize: function(options) {
        $('form[name="microsoft_settings"] :input[id*="microsoft_settings_oro_microsoft_integration"]')
            .on('change.microsoft_enable_oauth', function() {
                let disabled = false;
                const $settings = options._sourceElement.closest('form[name="microsoft_settings"]');

                if ($settings.find('input[id*="client_id"]').val().trim().length === 0) {
                    disabled = true;
                } else if ($settings.find('input[id*="client_secret"]').val().trim().length === 0) {
                    disabled = true;
                } else if ($settings.find('input[id*="tenant"]').val().trim().length === 0) {
                    disabled = true;
                }
                options._sourceElement.find('input[type=checkbox]:not(:checked)').prop('disabled', disabled);
            });
    },

    /**
     * @inheritDoc
     */
    dispose: function() {
        $('form[name="microsoft_settings"] :input[id*="microsoft_settings_oro_microsoft_integration"]')
            .off('change.microsoft_enable_oauth');

        return MicrosoftSyncCheckbox.__super__.dispose.call(this);
    }
});

export default MicrosoftSyncCheckbox;
