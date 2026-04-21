import BaseView from 'oroui/js/app/views/base/view';
import mediator from 'oroui/js/mediator';
import routing from 'routing';
import scrollHelper from 'oroui/js/tools/scroll-helper';
import tools from 'oroui/js/tools';
import __ from 'orotranslation/js/translator';

const SwitchOrganizationView = BaseView.extend({
    /**
     * @inheritdoc
     */
    autoRender: true,

    optionNames: BaseView.prototype.optionNames.concat(['organizations']),

    /**
     * @type {Array|null}
     * Static list of organizations [{id, name}, ...] passed from the server-side template.
     * When provided, the select2 dropdown uses this data directly instead of making API requests.
     */
    organizations: null,

    events: {
        'change input[type="hidden"]': 'onChange',
        'select2:select input[type="hidden"]': 'onChange',
        'select2-open input[type="hidden"]': 'onSelectOpen',
        'select2-close input[type="hidden"]': 'onSelectClose'
    },

    dropdownSelector: '.select2-organization-switcher__dropdown',

    /**
     * @inheritdoc
     */
    constructor: function SwitchOrganizationView(options) {
        SwitchOrganizationView.__super__.constructor.call(this, options);
    },

    initialize(options) {
        SwitchOrganizationView.__super__.initialize.call(this, options);
        this.prepareSelect2Config();
    },

    prepareSelect2Config() {
        const instance = this.$el.find('input[type="hidden"]').data('select2');
        const opts = instance?.opts;
        if (opts) {
            opts.formatSearching = function() {
                return __('Loading...');
            };

            if (this.organizations) {
                // Filter the pre-loaded organizations locally on each keystroke - avoids hitting the autocomplete
                // endpoint when the whole list is already on the client.
                const staticData = this.organizations;
                opts.query = function(query) {
                    const term = (query.term || '').toLowerCase();
                    const results = term
                        ? staticData.filter(item => item.name.toLowerCase().includes(term))
                        : staticData;
                    query.callback({results, more: false});
                };
            }
        }
    },

    render() {
        this.$dropdown = this.$(this.dropdownSelector);
    },

    onChange(event) {
        const orgId = event.target.value;
        if (orgId) {
            mediator.trigger('openLink:before', {
                target: {
                    href: routing.generate('oro_security_switch_organization', {id: orgId})
                }
            });
        }
    },

    onSelectOpen() {
        if (tools.isMobile()) {
            // Disable Body Touch Scroll if dropdown is fullscreen
            if (this.$dropdown.outerWidth() === document.documentElement.offsetWidth) {
                scrollHelper.disableBodyTouchScroll();
            }
        }
    },

    onSelectClose() {
        if (tools.isMobile()) {
            scrollHelper.enableBodyTouchScroll();
        }
    },

    dispose() {
        if (this.disposed) {
            return;
        }

        delete this.$dropdown;

        SwitchOrganizationView.__super__.dispose.call(this);
    }
});

export default SwitchOrganizationView;
