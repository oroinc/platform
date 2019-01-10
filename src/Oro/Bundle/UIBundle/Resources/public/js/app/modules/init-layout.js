define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools',
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
    $(function() {
        var $pageTitle = $('#page-title');
        if ($pageTitle.length) {
            document.title = $('<div.>').html($('#page-title').text()).text();
        }
        layout.hideProgressBar();

        // fixes submit by enter key press on select element
        $(document).on('keydown', 'form select', function(e) {
            if (e.keyCode === 13) {
                $(e.target.form).submit();
            }
        });

        var mainMenu = $('#main-menu');
        var sideMainMenu = $('#side-menu');

        // trigger refresh of current page if active menu-item is clicked, despite the Backbone router limitations
        (sideMainMenu.length ? sideMainMenu : mainMenu).on('click', 'li.active a', function(e) {
            var $target = $(e.target).closest('a');
            if (!$target.hasClass('unclickable') && $target[0] !== undefined && $target[0].pathname !== undefined) {
                if (mediator.execute('compareUrl', $target[0].pathname)) {
                    mediator.execute('refreshPage');
                    return false;
                }
            }
        });

        mainMenu.mouseover(function() {
            $(document).trigger('clearMenus'); // hides all opened dropdown menus
        });

        mediator.on('page:beforeChange', function() {
            $(document).trigger('clearMenus'); // hides all opened dropdown menus
        });

        if (tools.isMobile()) {
            /**
             * When a dropdown occupies the whole screen width, like modal dialog, we need to lock page scroll
             * to avoid moving elements behind the dropdown
             */
            $(document).on('shown.bs.dropdown', '.dropdown, .dropup, .dropleft, .dropright', function(e) {
                if (e.namespace !== 'bs.dropdown') {
                    // handle only events triggered with proper NS (omit just any shown events)
                    return;
                }
                var $html = $('html');
                var $dropdownMenu = $('>.dropdown-menu', this);

                if ($dropdownMenu.css('position') === 'fixed' && $dropdownMenu.outerWidth() === $html.width()) {
                    $html.addClass('modal-dropdown-shown');
                    $(this).one('hide.bs.dropdown', function() {
                        $html.removeClass('modal-dropdown-shown');
                    });
                }
            });
        }

        // fix + extend bootstrap.collapse functionality
        $(document).on('click.collapse.data-api', '[data-action^="accordion:"]', function(e) {
            var $elem = $(e.target);
            var action = $elem.data('action').slice(10);
            var method = {'expand-all': 'show', 'collapse-all': 'hide'}[action];
            var $target = $($elem.attr('data-target') || e.preventDefault() || $elem.attr('href'));
            $target.find('.collapse').collapse({toggle: false}).collapse(method);
        });
        $(document).on('shown.collapse.data-api hidden.collapse.data-api', '.accordion-body', function(e) {
            if (e.target === e.currentTarget) { // prevent processing if an event comes from child element
                var $toggle = $(e.target).closest('.accordion-group').find('[data-toggle=collapse]:first');
                $toggle.toggleClass('collapsed', e.type !== 'shown');
            }
        });
    });

    /* ============================================================
     * from height_fix.js
     * ============================================================ */
    // @TODO should be refactored in BAP-4020
    $(function() {
        var adjustHeight;

        if (tools.isMobile()) {
            adjustHeight = function() {
                layout.updateResponsiveLayout();
                mediator.trigger('layout:reposition');
            };
        } else {
            adjustHeight = function() {
                layout.updateResponsiveLayout();

                var sfToolbar = $('.sf-toolbarreset');
                var debugBarHeight = sfToolbar.is(':visible') ? sfToolbar.outerHeight() : 0;

                scrollspy.adjust();

                var fixDialog = 2;
                var footersHeight = ($('.sf-toolbar').height() || 0) + $('#footer').height();

                $('#dialog-extend-fixed-container').css({
                    position: 'fixed',
                    bottom: footersHeight + fixDialog,
                    zIndex: 9999
                });

                $('#page').css({
                    'padding-bottom': debugBarHeight
                });

                mediator.trigger('layout:reposition');
            };
        }

        layout.onPageRendered(adjustHeight);

        $(window).on('resize', _.debounce(adjustHeight, 40));

        mediator.on('page:afterChange', adjustHeight);

        mediator.on('layout:adjustReloaded', adjustHeight);
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
    });

    /* ============================================================
     * from remove.confirm.js
     * ============================================================ */
    $(function() {
        $(document).on('click', '.remove-button', function(e) {
            var el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                var data = {content: el.data('message')};

                var okText = el.data('ok-text');
                if (okText) {
                    data.okText = okText;
                }

                var title = el.data('title');
                if (title) {
                    data.title = title;
                }

                var cancelText = el.data('cancel-text');
                if (cancelText) {
                    data.cancelText = cancelText;
                }

                var confirm = new DeleteConfirmation(data);

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
                                mediator.execute('showFlashMessage', 'success', el.data('success-message'));
                            }
                        },
                        errorHandlerMessage: function() {
                            return el.data('error-message') || true;
                        },
                        complete: function() {
                            mediator.execute('hideLoading');
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
        var rowCountAdd = 1;
        if ($(this).data('row-add-only-one')) {
            $(this).removeData('row-add-only-one');
        } else {
            rowCountAdd = $(containerSelector).data('row-count-add') || 1;
        }

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

    // TODO: implement clone row

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
