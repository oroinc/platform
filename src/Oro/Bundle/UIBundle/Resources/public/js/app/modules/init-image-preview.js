import $ from 'jquery';
import _ from 'underscore';
import ImagePreviewModal from 'oroui/js/image-preview-model';

/**
 * On click on gallery element (with 'data-gallery' attribute):
 * find all gallery elements from the same gallery group,
 * dynamically generate array of gallery elements and show the gallery.
 */
$(document).on('click.gallery', function(e) {
    let $target = $(e.target);

    if ($target.is('.thumbnail')) { // if click was done on thumbnail image, use parent element as a target
        $target = $target.parent();
    }

    if ($target.is('picture')) { // if picture tag was used to embed an image
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
            const src = $item.attr('href');
            const sources = $item.attr('data-sources');

            if (_.indexOf(images, src) === -1) {
                images.push(src);
                const el = {};
                el.src = src;
                el.sources = sources ? JSON.parse(sources) : [];
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

        const modal = new ImagePreviewModal({
            images: dynamicEl,
            currentSlide,
            handleClose: true
        });

        modal.open();
        e.preventDefault();
    }
});
