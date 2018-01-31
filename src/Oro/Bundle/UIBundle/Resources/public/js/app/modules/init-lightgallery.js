define(['jquery', 'underscore', 'lightgallery', 'lightgallery.print'], function($, _) {
    'use strict';

    /**
     * On click on gallery element (with 'data-gallery' attribte):
     * find all gallery elements from the same gallery group,
     * dynamically generate array of gallery elements and show the gallery.
     */
    $(document).on('click.gallery', function(e) {
        var $target = $(e.target);
        if ($target.is('.thumbnail')) { // if click was done on thumbnail image, use parent element as a target
            $target = $target.parent();
        }
        if ($target.data('gallery')) {
            var galleryId = $target.data('gallery');
            var $items = $('[data-gallery]').filter(function() {
                return $(this).data('gallery') === galleryId;
            });
            var dynamicEl = [];
            var images = [];
            var currentSlide = 0;
            var i = 0;
            $items.each(function() {
                var $item = $(this);
                var src = $item.attr('href');
                if (_.indexOf(images, src) === -1) {
                    images.push(src);
                    var el = {};
                    el.src = src;
                    var img = $item.find('.thumbnail');
                    if (img.length) {
                        el.thumb = img.css('background-image').replace(/^url\(['"]?/, '').replace(/['"]?\)$/, '');
                    } else {
                        el.thumb = el.src;
                    }
                    if ($item.data('filename')) {
                        el.subHtml = _.escape($item.data('filename'));
                    }
                    dynamicEl.push(el);
                    if (src === $target.attr('href')) {
                        currentSlide = i;
                    }
                    i++;
                }
            });

            $(this).lightGallery({
                dynamic: true,
                dynamicEl: dynamicEl,
                index: currentSlide,
                showAfterLoad: false,
                hash: false
            }).on('onCloseAfter.lg', function() {
                var $el = $(this);
                var lightGallery = $el.data('lightGallery');
                if (lightGallery) {
                    lightGallery.destroy(true); // fully destroy gallery on close
                }
                $el.off('onCloseAfter.lg');
            });
            e.preventDefault();
        }
    });
});
