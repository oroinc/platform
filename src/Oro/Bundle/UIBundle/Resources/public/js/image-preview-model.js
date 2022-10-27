import $ from 'jquery';
import __ from 'orotranslation/js/translator';
import ModalView from 'oroui/js/modal';
import template from 'tpl-loader!oroui/templates/image-preview-modal.html';
import 'slick';

const ImagePreviewModal = ModalView.extend({
    /**
     * @inheritdoc
     */
    className: 'modal oro-modal-image-preview',

    /**
     * @inheritdoc
     */
    template,

    /**
     * @inheritdoc
     */
    slider: null,

    hasOpenModal: false,

    /**
     * @inheritdoc
     */
    events: {
        ...ModalView.prototype.events(),
        'beforeChange .images-list': 'onBeforeSlideChange',
        'click .print': 'printSlide',
        'click .download': 'downloadSlide',
        'click': 'onClickSlide',
        'mousemove .modal-dialog': 'onMouseMove'
    },

    /**
     * @inheritdoc
     */
    listen: {
        ...ModalView.prototype.listen,
        'layout:reposition mediator': 'onLayoutReposition'
    },

    /**
     * @inheritdoc
     */
    constructor: function ImagePreviewModal(options) {
        ImagePreviewModal.__super__.constructor.call(this, options);
    },

    /**
     * @inheritdoc
     */
    getTemplateData() {
        const data = ImagePreviewModal.__super__.getTemplateData.call(this);

        data.counterCurrent = this.options.currentSlide + 1;
        data.counterAll = this.options.images.length;
        data.images = this.options.images;
        return data;
    },

    onModalHidden() {
        if (this.disposed) {
            return;
        }
        ImagePreviewModal.__super__.onModalHidden.call(this);
    },

    /**
     * @inheritdoc
     */
    render() {
        ImagePreviewModal.__super__.render.call(this);

        this.$('.modal-footer').remove();
        this.$('.download').attr('href', this.options.images[this.options.currentSlide].src);

        this.slider = this.$('.images-list').slick({
            infinite: true,
            initialSlide: this.options.currentSlide,
            prevArrow: `<button type="button" aria-label="${__('Previous')}" class="slick-prev">
                            <span class="fa-arrow-left"></span>
                        </button>`,
            nextArrow: `<button type="button" aria-label="${__('Next')}" class="slick-next">
                            <span class="fa-arrow-right"></span>
                        </button>`,
            lazyLoad: 'ondemand',
            mobileFirst: true,
            centerMode: true,
            variableWidth: true
        });
    },

    delegateEvents() {
        ImagePreviewModal.__super__.delegateEvents.call(this);

        this.$('.images-list__item').one(`load${this.eventNamespace()}`, this.onLazyLoaded.bind(this));

        return this;
    },

    /**
     * Add specific className to backdrop
     * @param {function} callback
     * @returns {ImagePreviewModal}
     */
    open(callback) {
        ImagePreviewModal.__super__.open.call(this, callback);

        const $backdrop = $(`.modal-backdrop:eq(${ModalView.count - 1})`);
        $backdrop.addClass('image-preview');

        this.onLayoutReposition();

        return this;
    },

    /**
     * Update download link href and update current counter number
     * @param {Event} event
     * @param {object} slick
     * @param {number} currentSlide
     * @param {number} nextSlide
     */
    onBeforeSlideChange(event, slick, currentSlide, nextSlide) {
        this.$('.counter-current').text(nextSlide + 1);
        this.$('.download').attr('href', this.options.images[nextSlide].src);
    },

    /**
     * Remove lazy className after load image
     * @param {Event} event
     */
    onLazyLoaded(event) {
        $(event.target).closest('.slick-active').removeClass('lazy-loading');
    },

    downloadSlide(event) {
        event.stopPropagation();
    },

    /**
     * Print current slide
     * @param {Event} event
     */
    printSlide(event) {
        event.preventDefault();

        const currentIndex = this.$('.images-list').slick('slickCurrentSlide');
        const $image = this.$(`.images-list [data-slick-index="${currentIndex}"]`);
        const printHtml = $image.prop('outerHTML');

        const frame = $('<iframe/>', {
            name: 'print-frame'
        });
        frame.css({
            position: 'absolute',
            top: '-10000px'
        });
        $('body').append(frame);

        const contentWindow = frame.prop('contentWindow');
        const contentDocument = frame.prop('contentDocument');
        const frameDoc = contentWindow ? contentWindow
            : contentDocument.document ? contentDocument.document : contentDocument;
        frameDoc.document.open();
        const title = 'Print';

        frameDoc.document.write(`
            <html>
                <head>
                    <title>${title}</title>
                </head>
                <body>
                    ${printHtml}
                </body>
            </html>
        `);
        frameDoc.document.close();

        $(window.frames['print-frame']).on('load', function() {
            const self = $(this).get(0);
            setTimeout(function() {
                self.focus();
                self.print();
                frame.remove();
            }, 500);
        });
    },

    /**
     * Update slider width on resize window
     */
    onLayoutReposition() {
        this.slider.find('.slick-slide').css('max-width', this.getSliderWidth());
        this.slider.find('.images-list__item').css('max-height', this.getSliderHeight());
    },

    /**
     * Get slider container width
     */
    getSliderWidth() {
        return this.slider.innerWidth() - parseInt(this.slider.slick('slickGetOption', 'centerPadding')) * 2;
    },

    /**
     * Get slider container width
     */
    getSliderHeight() {
        return window.innerHeight - parseInt(this.slider.slick('slickGetOption', 'centerPadding')) * 2;
    },

    /**
     * Show controls
     */
    onMouseMove() {
        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        const $modalDialog = this.$('.modal-dialog');
        $modalDialog.removeClass('hide-controls');

        this.timeout = setTimeout(() => {
            $modalDialog.addClass('hide-controls');
        }, 5000);
    },

    /**
     * Handle click to close modal
     * @param {Element} target
     */
    onClickSlide({target}) {
        if (['IMG', 'BUTTON', 'SPAN'].includes(target.tagName)) {
            return;
        }

        this.close();
    },

    /**
     * @inheritdoc
     */
    dispose() {
        if (this.disposed) {
            return;
        }

        if (this.timeout) {
            clearTimeout(this.timeout);
        }

        this.slider.slick('unslick');

        ImagePreviewModal.__super__.dispose.call(this);
    }
});

export default ImagePreviewModal;
