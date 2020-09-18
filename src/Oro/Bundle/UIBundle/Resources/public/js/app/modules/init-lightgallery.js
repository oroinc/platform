define([
    'jquery',
    'underscore',
    'oroui/js/tools',
    'lightgallery',
    'lightgallery.print'
], function($, _, tools) {
    'use strict';

    /**
     * On click on gallery element (with 'data-gallery' attribte):
     * find all gallery elements from the same gallery group,
     * dynamically generate array of gallery elements and show the gallery.
     */
    $(document).on('click.gallery', function(e) {
        let $target = $(e.target);
        if ($target.is('.thumbnail')) { // if click was done on thumbnail image, use parent element as a target
            $target = $target.parent();
        }
        if ($target.data('gallery')) {
            const galleryId = $target.data('gallery');
            const $items = $('[data-gallery]').filter(function() {
                return $(this).data('gallery') === galleryId;
            });
            const dynamicEl = [];
            const images = [];
            let currentSlide = 0;
            let i = 0;
            $items.each(function() {
                const $item = $(this);
                let src = $item.attr('href');

                // Hack for IE11
                if (tools.isIE11() && $item.closest('[data-ie-preview]').length) {
                    src = $item.closest('[data-ie-preview]').data('ie-preview');
                }

                if (_.indexOf(images, src) === -1) {
                    images.push(src);
                    const el = {};
                    el.src = src;
                    const img = $item.find('.thumbnail');
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
                const $el = $(this);
                const lightGallery = $el.data('lightGallery');
                if (lightGallery) {
                    lightGallery.destroy(true); // fully destroy gallery on close
                }
                $el.off('onCloseAfter.lg');
            });
            e.preventDefault();
        }
    });
});
