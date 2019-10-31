define(['jquery'], function($) {
    'use strict';

    const defaults = {
        print: true
    };

    const Print = function(element) {
        // get lightGallery core plugin data
        this.core = $(element).data('lightGallery');

        // extend module defalut settings with lightGallery core settings
        this.core.s = $.extend({}, defaults, this.core.s);

        this.init();

        return this;
    };

    Print.prototype.init = function() {
        if (this.core.s.print) {
            const printButton = '<span class="fa-print lg-print lg-icon"></span>';
            this.core.$outer.find('.lg-toolbar').append(printButton);
            this.print();
        }
    };

    Print.prototype.print = function() {
        const _this = this;

        this.core.$outer.find('.lg-print').on('click.lg', function() {
            _this.printCurrentSlide();
        });
    };

    /**
     * Create hidden frame with current image and print it
     */
    Print.prototype.printCurrentSlide = function() {
        const $image = this.core.$slide.eq(this.core.index).find('.lg-object');
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
        const title = $('.lg-sub-html').text();
        frameDoc.document.write('<html><head><title>' + title + '</title>');
        frameDoc.document.write('</head><body>');
        frameDoc.document.write(printHtml);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();
        $(window.frames['print-frame']).on('load', function() {
            const self = $(this).get(0);
            setTimeout(function() {
                self.focus();
                self.print();
                frame.remove();
            }, 500);
        });
    };

    Print.prototype.destroy = function() {

    };

    $.fn.lightGallery.modules.print = Print;
});
