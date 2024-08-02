import BaseView from 'oroui/js/app/views/base/view';

const ThemeConfigurationChangePreview = BaseView.extend({
    /**
     * @inheritdoc
     */
    optionNames: BaseView.prototype.optionNames.concat(['previewSource', 'previewAlt', 'defaultPreview']),

    /**
     * @property {boolean}
     */
    isDisabled: false,

    /**
     * Any valid jQuery selector to listen to change event on elements
     * @property {string}
     */
    onChangeFieldsSelector: '[name^="theme_configuration[configuration]"]',

    /**
     * @inheritdoc
     */
    events() {
        return {
            [`change ${this.onChangeFieldsSelector}`]: 'onChange'
        };
    },

    /**
     * @inheritdoc
     */
    constructor: function ThemeConfigurationChangePreview(options) {
        ThemeConfigurationChangePreview.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    initialize(options) {
        ThemeConfigurationChangePreview.__super__.initialize.call(this, options);

        if (this.previewSource === void 0) {
            throw new Error('Option "previewSource" must be defined');
        }

        if (!this.previewAlt) {
            throw new Error('Option "previewAlt" must be defined');
        }
    },

    /**
     * @inheritDoc
     */
    render() {
        ThemeConfigurationChangePreview.__super__.render.call(this);

        this.$('[data-role="preview"]').html(this.createPreview());

        return this;
    },

    /**
     * Shows the latest chosen preview
     * @param {HTMLElement} el
     */
    showPreview(el) {
        const preview = this.getPreview();

        if (!preview) {
            return;
        }
        const source = this.getSource(el);

        if (source === preview.src) {
            return;
        }

        preview.src = source;
    },

    /**
     * Event handler
     * @param {Object} event
     */
    onChange(event) {
        this.showPreview(event.target);
    },
    /**
     * Event handler on preview is loaded
     * {Object} event
     */
    onPreviewLoaded(event) {
        this.enable();
    },

    /**
     * Event handler on preview is not loaded
     * {Object} event
     */
    onPreviewFailed(event) {
        this.disable();
    },

    /**
     * Creates an image to show a preview
     * @returns {HTMLImageElement}
     */
    createPreview() {
        const img = new Image();

        img.classList.add('theme-configuration-img', 'img-fluid');
        img.dataset.role = 'img';
        img.src = this.previewSource;
        img.alt = this.previewAlt;
        img.onload = this.onPreviewLoaded.bind(this);
        img.onerror = this.onPreviewFailed.bind(this);

        return img;
    },

    /**
     * Finds an image
     * @returns {HTMLImageElement|null}
     */
    getPreview() {
        return this.el.querySelector('[data-role="img"]');
    },

    /**
     * Gets a source of a new image to show
     * @param {HTMLElement} el
     * @returns {string}
     */
    getSource(el) {
        if (!el) {
            return '';
        }

        if (el.tagName === 'SELECT') {
            return this.getSourceOfSelect(el);
        }

        if (el.type === 'checkbox') {
            return this.getSourceOfCheckbox(el);
        }

        return this.getSourceOfElement(el);
    },

    /**
     * Gets a source of a form element
     * @param {HTMLElement} el
     * @returns {string}
     */
    getSourceOfElement(el) {
        const preview = el.dataset?.preview;

        if (!preview) {
            return this.getDefaultPreview();
        }
        return preview;
    },

    /**
     * Gets a source of a select or select2 elements
     * @param {HTMLElement} el
     * @returns {string}
     */
    getSourceOfSelect(el) {
        return this.getSourceOfElement(el.options[el.selectedIndex]);
    },

    /**
     * Gets a source of a checkbox element
     * @param {HTMLElement} el
     * @returns {string}
     */
    getSourceOfCheckbox(el) {
        const preview = el.checked ? el.dataset?.previewChecked : el.dataset.previewUnchecked;

        if (!preview) {
            return this.getDefaultPreview();
        }

        return preview;
    },

    /**
     * Gets a fullback source to show
     *
     * @returns {string}
     */
    getDefaultPreview() {
        return this?.defaultPreview || '';
    },

    /**
     * Enables UI
     */
    enable() {
        if (this.isDisabled === false) {
            return;
        }

        this.isDisabled = false;

        const preview = this.getPreview();

        if (!preview) {
            return;
        }
        preview.classList.remove('no-preview');
        this.$('[data-toggle="collapse"]').prop('disabled', false);
        this.$('.collapse').collapse('show');
    },

    /**
     * Disables UI
     */
    disable() {
        if (this.isDisabled === true) {
            return;
        }

        this.isDisabled = true;
        const preview = this.getPreview();

        if (preview) {
            preview.classList.add('no-preview');
        }
        this.$('.collapse').collapse('hide');
        this.$('[data-toggle="collapse"]').attr('disabled', true);
    }
});

export default ThemeConfigurationChangePreview;
