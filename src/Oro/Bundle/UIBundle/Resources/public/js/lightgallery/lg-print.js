define(['jquery'], function($) {

    'use strict';

    var defaults = {
        print: true
    };

    var Print = function(element) {

        // get lightGallery core plugin data
        this.core = $(element).data('lightGallery');

        // extend module defalut settings with lightGallery core settings
        this.core.s = $.extend({}, defaults, this.core.s);

        this.init();

        return this;
    };

    Print.prototype.init = function() {
        if (this.core.s.print) {
            var printButton = '<span class="icon icon-print lg-print lg-icon"></span>';
            this.core.$outer.find('.lg-toolbar').append(printButton);
            this.print();
        }
    };

    Print.prototype.print = function() {
        var _this = this;

        this.core.$outer.find('.lg-print').on('click.lg', function() {
            _this.printCurrentSlide();
        });

    };

    /**
     * Create hidden frame with current image and print it
     */
    Print.prototype.printCurrentSlide = function() {
        var $image = this.core.$slide.eq(this.core.index).find('.lg-object');
        var printHtml = $image.prop('outerHTML');

        var frame = $('<iframe/>', {
            'name': 'print-frame'
        });
        frame.css({
            'position': 'absolute',
            'top': '-10000px'
        });
        $('body').append(frame);

        var contentWindow = frame.prop('contentWindow');
        var contentDocument = frame.prop('contentDocument');
        var frameDoc = contentWindow ? contentWindow :
            contentDocument.document ? contentDocument.document : contentDocument;
        frameDoc.document.open();
        var title = $('.lg-sub-html').text();
        frameDoc.document.write('<html><head><title>' + title + '</title>');
        frameDoc.document.write('</head><body>');
        frameDoc.document.write(printHtml);
        frameDoc.document.write('</body></html>');
        frameDoc.document.close();
        $(window.frames['print-frame']).load(function() {
            var self = $(this).get(0);
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
