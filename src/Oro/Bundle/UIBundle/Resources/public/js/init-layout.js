
require(['oroui/js/mediator'], function(mediator) {
    'use strict';

    mediator.once('page:afterChange', function() {
        //@TODO remove delay, when afterChange event will
        // take in account rendering from inline scripts
        setTimeout(function() {
            // emulates 'document ready state' for selenium tests
            document['page-rendered'] = true;
            mediator.trigger('page-rendered');
        }, 50);
    });
});

require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools',
        'oroui/js/mediator', 'oroui/js/layout',
        'oroui/js/delete-confirmation', 'oroui/js/scrollspy',
        'bootstrap', 'jquery-ui'
    ], function($, _, __, tools, mediator, layout, DeleteConfirmation, scrollspy) {
    'use strict';

    /**
     * Remove selection after page change
     */
    mediator.on('page:beforeChange', function clearSelection() {
        if (document.selection) {
            document.selection.empty();
        } else if (window.getSelection) {
            window.getSelection().removeAllRanges();
        }
    });

    /* ============================================================
     * from layout.js
     * ============================================================ */

    var realWidth = function($selector) {
        if ($selector instanceof $ && $selector.length > 0) {
            return $selector[0].getBoundingClientRect().width;
        } else {
            return 0;
        }
    };

    $(function() {
        var $pageTitle = $('#page-title');
        if ($pageTitle.size()) {
            document.title = $('<div.>').html($('#page-title').text()).text();
        }
        layout.hideProgressBar();

        /* side bar functionality */
        $('div.side-nav').each(function() {
            var myParent = $(this);
            var myParentHolder = $(myParent).parent().height() - 18;
            $(myParent).height(myParentHolder);
            /* open close bar */
            $(this).find('span.maximize-bar').click(function() {
                if (($(myParent).hasClass('side-nav-open')) || ($(myParent).hasClass('side-nav-locked'))) {
                    $(myParent).removeClass('side-nav-locked side-nav-open');
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').removeClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').removeClass('right-locked');
                    }
                    $(myParent).find('.bar-tools').css({
                        height: 'auto',
                        overflow: 'visible'
                    });
                } else {
                    $(myParent).addClass('side-nav-open');
                    var openBarHeight = $('div.page-container').height() - 20;
                    var testBarScroll = $(myParent).find('.bar-tools').height();
                    /* minus top-padding and bottom-padding */
                    $(myParent).height(openBarHeight);
                    if (openBarHeight < testBarScroll) {
                        $(myParent).find('.bar-tools').height((openBarHeight - 20)).css({
                            overflow: 'auto'
                        });
                    }
                }
            });

            /* lock&unlock bar */
            $(this).find('span.lock-bar').click(function() {
                if ($(this).hasClass('lock-bar-locked')) {
                    $(myParent).addClass('side-nav-open')
                        .removeClass('side-nav-locked');
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').removeClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').removeClass('right-locked');
                    }
                } else {
                    $(myParent).addClass('side-nav-locked')
                        .removeClass('side-nav-open');
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').addClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').addClass('right-locked');
                    }

                }
                $(this).toggleClass('lock-bar-locked');
            });

            /* open&close popup for bar items when bar is minimized. */
            $(this).find('.bar-tools li').each(function() {
                var myItem = $(this);
                $(myItem).find('.sn-opener').click(function() {
                    $(myItem).find('div.nav-box').fadeToggle('slow');

                    var $barOverlay = $('#bar-drop-overlay');
                    var $page = $('#page');
                    var overlayHeight = $page.height();
                    var overlayWidth = $page.children('.wrapper').width();
                    $barOverlay.width(overlayWidth).height(overlayHeight);
                    $barOverlay.toggleClass('bar-open-overlay');
                });
                $(myItem).find('span.close').click(function() {
                    $(myItem).find('div.nav-box').fadeToggle('slow');
                    $('#bar-drop-overlay').toggleClass('bar-open-overlay');
                });
                $('#bar-drop-overlay').on({
                    click: function() {
                        $(myItem).find('div.nav-box').animate({
                            opacity: 0,
                            display: 'none'
                        }, function() {
                            $(this).css({
                                opacity: 1,
                                display: 'none'
                            });
                        });
                        $('#bar-drop-overlay').removeClass('bar-open-overlay');
                    }
                });
            });
            /* open content for open bar */
            $(myParent).find('ul.bar-tools > li').each(function() {
                var _barLi = $(this);
                $(_barLi).find('span.open-bar-item').click(function() {
                    $(_barLi).find('div.nav-content').slideToggle();
                    $(_barLi).toggleClass('open-item');
                });
            });
        });

        /* ============================================================
         * Oro Dropdown close prevent
         * ============================================================ */
        var dropdownToggles = $('.oro-dropdown-toggle');
        dropdownToggles.click(function(e) {
            var $parent = $(this).parent().toggleClass('open');
            if ($parent.hasClass('open')) {
                $parent.find('.dropdown-menu').focus();
                $parent.find('input[type=text]').first().focus().select();
            }
        });
        $(document).on('focus.dropdown.data-api', '[data-toggle=dropdown]', _.debounce(function(e) {
            $(e.target).parent().find('input[type=text]').first().focus();
        }, 10));

        $(document).on('keyup.dropdown.data-api', '.dropdown-menu', function(e) {
            if (e.keyCode === 27) {
                $(e.currentTarget).parent().trigger('tohide.bs.dropdown');
            }
        });

        // fixes submit by enter key press on select element
        $(document).on('keydown', 'form select', function(e) {
            if (e.keyCode === 13) {
                $(e.target.form).submit();
            }
        });

        $(document).on('focus', '.select2-focusser, .select2-input', function(e) {
            $('.hasDatepicker').datepicker('hide');
        });

        var openDropdownsSelector = '.dropdown.open, .dropdown .open, .oro-drop.open, .oro-drop .open';
        $('html').click(function(e) {
            var $target = $(e.target);
            var clickingTarget = null;
            if ($target.hasClass('dropdown') || $target.hasClass('oro-drop')) {
                clickingTarget = $target;
            } else {
                clickingTarget = $target.closest('.dropdown, .oro-drop');
            }
            $(openDropdownsSelector).not(clickingTarget).trigger('tohide.bs.dropdown');
        });

        $('#main-menu').mouseover(function() {
            $(openDropdownsSelector).trigger('tohide.bs.dropdown');
        });

        mediator.on('page:beforeChange', function() {
            $('.dot-menu.dropdown.open, .nav .dropdown.open').trigger('tohide.bs.dropdown');
            $('.dropdown:hover > .dropdown-menu').hide().addClass('manually-hidden');
        });
        mediator.on('page:afterChange', function() {
            $('.dropdown .dropdown-menu.manually-hidden').css('display', '');
        });

        // fix + extend bootstrap.collapse functionality
        $(document).on('click.collapse.data-api', '[data-action^="accordion:"]', function(e) {
            var $elem = $(e.target);
            var action = $elem.data('action').slice(10);
            var method = {'expand-all': 'show', 'collapse-all': 'hide'}[action];
            var $target = $($elem.attr('data-target') || e.preventDefault() || $elem.attr('href'));
            $target.find('.collapse').collapse({toggle: false}).collapse(method);
        });
        $(document).on('click.collapse.data-api', '[data-toggle=collapse]', function(e) {
            var $toggle = $(this);
            var target = $toggle.attr('data-target') || $toggle.attr('href');
            $toggle = $toggle.add('[data-target="' + target + '"], [href="' + target + '"]');
            $toggle.toggleClass('collapsed', !$(target).hasClass('in'));
        });
        $(document).on('shown.collapse.data-api hidden.collapse.data-api', '.accordion-body', function(e) {
            if (e.target === e.currentTarget) {   // prevent processing if an event comes from child element
                var $toggle = $(e.target).closest('.accordion-group').find('[data-toggle=collapse]:first');
                $toggle.toggleClass('collapsed', e.type !== 'shown');
            }
        });
    });

    /* ============================================================
     * from height_fix.js
     * ============================================================ */
    //@TODO should be refactored in BAP-4020
    $(function() {
        var adjustHeight;

        if (tools.isMobile()) {
            adjustHeight = function() {
                layout.updateResponsiveLayout();
                mediator.trigger('layout:reposition');
            };
        } else {
            /* dynamic height for central column */
            var anchor = $('#bottom-anchor');
            var content = false;

            var initializeContent = function() {
                if (!content) {
                    content = $('.scrollable-container').filter(':parents(.ui-widget)');
                    if (!tools.isMobile()) {
                        content.css('overflow', 'inherit').last().css('overflow-y', 'auto');
                    } else {
                        content.css('overflow', 'hidden');
                        content.last().css('overflow-y', 'auto');
                    }
                }
            };
            var $main = $('#main');
            var $topPage = $('#top-page');
            var $leftPanel = $('#left-panel');
            var $rightPanel = $('#right-panel');
            adjustHeight = function() {
                initializeContent();

                // set width for #main container
                $main.width(realWidth($topPage) - realWidth($leftPanel) - realWidth($rightPanel));
                layout.updateResponsiveLayout();

                var sfToolbar = $('.sf-toolbarreset');
                var debugBarHeight = sfToolbar.is(':visible') ? sfToolbar.outerHeight() : 0;
                var anchorTop = anchor.position().top;
                var footerHeight = $('#footer:visible').height() || 0;
                var fixContent = 1;

                $(content.get().reverse()).each(function(pos, el) {
                    el = $(el);
                    el.height(anchorTop - el.position().top - footerHeight - debugBarHeight + fixContent);
                });

                // set height for #left-panel and #right-panel
                $leftPanel.add($rightPanel).height($main.height());

                scrollspy.adjust();

                var fixDialog = 2;
                var footersHeight = $('.sf-toolbar').height() + $('#footer').height();

                $('#dialog-extend-fixed-container').css({
                    position: 'fixed',
                    bottom: footersHeight + fixDialog,
                    zIndex: 9999
                });

                $('.sidebar').css({
                    'margin-bottom': footersHeight
                });

                mediator.trigger('layout:reposition');
            };

            if (!anchor.length) {
                anchor = $('<div id="bottom-anchor"/>')
                    .css({
                        position: 'fixed',
                        bottom: '0',
                        left: '0',
                        width: '1px',
                        height: '1px'
                    })
                    .appendTo($(document.body));
            }
        }

        var adjustReloaded = function() {
            content = false;
            adjustHeight();
        };

        layout.onPageRendered(adjustHeight);

        $(window).on('resize', _.debounce(adjustHeight, 40));

        mediator.on('page:afterChange', adjustReloaded);

        mediator.on('layout:adjustReloaded', adjustReloaded);
        mediator.on('layout:adjustHeight', adjustHeight);
        mediator.on('datagrid:rendered datagrid_filters:rendered widget_remove', scrollspy.adjust);

        adjustHeight();
    });

    /* ============================================================
     * from form_buttons.js
     * ============================================================ */
    $(document).on('click', '.action-button', function() {
        var actionInput = $('input[name = "input_action"]');
        actionInput.val($(this).attr('data-action'));
        $('#' + actionInput.attr('data-form-id')).submit();
    });

    /* ============================================================
     * from remove.confirm.js
     * ============================================================ */
    $(function() {
        $(document).on('click', '.remove-button', function(e) {
            var el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                var message = el.data('message');
                var confirm = new DeleteConfirmation({
                    content: message
                });

                confirm.on('ok', function() {
                    mediator.execute('showLoading');

                    $.ajax({
                        url: el.data('url'),
                        type: 'DELETE',
                        success: function(data) {
                            el.trigger('removesuccess');
                            var redirectTo = el.data('redirect');
                            if (redirectTo) {
                                mediator.execute('addMessage', 'success', el.data('success-message'));

                                // In case when redirectTo is current page just refresh it, otherwise redirect.
                                if (mediator.execute('compareUrl', redirectTo)) {
                                    mediator.execute('refreshPage');
                                } else {
                                    mediator.execute('redirectTo', {url: redirectTo});
                                }
                            } else {
                                mediator.execute('hideLoading');
                                mediator.execute('showFlashMessage', 'success', el.data('success-message'));
                            }
                        },
                        error: function() {
                            var message;
                            message = el.data('error-message') ||
                                __('Unexpected error occurred. Please contact system administrator.');
                            mediator.execute('hideLoading');
                            mediator.execute('showMessage', 'error', message);
                        }
                    });
                });
                confirm.open();
            }

            return false;
        });
    });

    /* ============================================================
     * from form/collection.js'
     * ============================================================ */

    var getOroCollectionInfo = function($listContainer) {
        var index = $listContainer.data('last-index') || $listContainer.children().length;
        var prototypeName = $listContainer.attr('data-prototype-name') || '__name__';
        var html = $listContainer.attr('data-prototype');

        return {
            nextIndex: index,
            prototypeHtml: html,
            prototypeName: prototypeName
        };
    };
    var getOroCollectionNextItemHtml = function(collectionInfo) {
        return collectionInfo.prototypeHtml
            .replace(new RegExp(collectionInfo.prototypeName, 'g'), collectionInfo.nextIndex);
    };

    var validateContainer = function($container) {
        var $validationField = $container.find('[data-name="collection-validation"]:first');
        var $form = $validationField.closest('form');
        if ($form.data('validator')) {
            $form.validate().element($validationField.get(0));
        }
    };

    $(document).on('click', '.add-list-item', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }
        var containerSelector = $(this).data('container') || '.collection-fields-list';
        var $listContainer = $(this).closest('.row-oro').find(containerSelector).first();
        var rowCountAdd = $(containerSelector).data('row-count-add') || 1;
        var collectionInfo = getOroCollectionInfo($listContainer);
        for (var i = 1; i <= rowCountAdd; i++) {
            var nextItemHtml = getOroCollectionNextItemHtml(collectionInfo);
            collectionInfo.nextIndex++;
            $listContainer.append(nextItemHtml)
                .trigger('content:changed')
                .data('last-index', collectionInfo.nextIndex);
        }
        $listContainer.find('input.position-input').each(function(i, el) {
            $(el).val(i);
        });
        validateContainer($listContainer);
    });

    $(document).on('click', '.addAfterRow', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }
        var $item = $(this).closest('.row-oro').parent();
        var $listContainer = $item.parent();
        var collectionInfo = getOroCollectionInfo($listContainer);
        var nextItemHtml = getOroCollectionNextItemHtml(collectionInfo);
        $item.after(nextItemHtml);
        $listContainer.trigger('content:changed')
            .data('last-index', collectionInfo.nextIndex + 1);

        $listContainer.find('input.position-input').each(function(i, el) {
            $(el).val(i);
        });
    });

    $(document).on('click', '.removeRow', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }

        var item;
        var closest = '*[data-content]';
        if ($(this).data('closest')) {
            closest = $(this).data('closest');
        }

        item = $(this).closest(closest);
        item.trigger('content:remove')
            .remove();
    });

    /**
     * Support for [data-focusable] attribute
     */
    $(document).on('click', 'label[for]', function(e) {
        var forAttribute = $(e.target).attr('for');
        var labelForElement = $('#' + forAttribute + ':first');
        if (labelForElement.is('[data-focusable]')) {
            e.preventDefault();
            labelForElement.trigger('set-focus');
        }
    });
});

require(['jquery', 'underscore', 'lightgallery', 'lightgallery.print'], function($, _) {
    'use strict';

    /**
     * On click on gallery element (with 'data-gallery' attribte):
     * find all gallery elements from the same gallery group,
     * dynamically generate array of gallery elements and show the gallery.
     */
    $(document).on('click.gallery', function(e) {
        var $target = $(e.target);
        if ($target.is('.thumbnail')) { //if click was done on thumbnail image, use parent element as a target
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
                $(this).data('lightGallery').destroy(true); //fully destroy gallery on close
                $(this).off('onCloseAfter.lg');
            });
            e.preventDefault();
        }
    });
});
