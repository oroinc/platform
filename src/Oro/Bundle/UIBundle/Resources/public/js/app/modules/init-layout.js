define(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools',
    'oroui/js/mediator', 'oroui/js/layout',
    'oroui/js/delete-confirmation', 'oroui/js/scrollspy', 'oroui/js/tools/scroll-helper'
], function($, _, __, tools, mediator, layout, DeleteConfirmation, scrollspy, scrollHelper) {
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
        const $pageTitle = $('#page-title');
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

        $(document).on('keydown', 'a[role=button], [data-emulate-btn-press]', function(e) {
            if (
                (e.keyCode === 32 || e.keyCode === 13) &&
                !e.isDefaultPrevented()
            ) {
                $(e.target).click();
                e.preventDefault();
            }
        });

        $(document).on('keydown click', 'textarea[data-autoresize]', e => {
            const el = e.target;
            setTimeout(() => {
                if (el.scrollHeight > el.offsetHeight) {
                    el.style.cssText = 'height:auto; padding:0';
                    el.style.cssText = '-moz-box-sizing:content-box';
                    el.style.cssText = 'height:' + el.scrollHeight + 'px';
                }
            }, 0);
        });

        const mainMenu = $('#main-menu');
        const sideMainMenu = $('#side-menu');

        // trigger refresh of current page if active menu-item is clicked, despite the Backbone router limitations
        (sideMainMenu.length ? sideMainMenu : mainMenu).on('click', 'li.active a', function(e) {
            const $target = $(e.target).closest('a');
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
                const $html = $('html');
                const $dropdownMenu = $('>.dropdown-menu', this);

                if ($dropdownMenu.css('position') === 'fixed' && $dropdownMenu.outerWidth() === $html.width()) {
                    scrollHelper.disableBodyTouchScroll();
                    $(this).one('hide.bs.dropdown', function() {
                        scrollHelper.enableBodyTouchScroll();
                    });
                }
            });
        }

        // fix + extend bootstrap.collapse functionality
        $(document).on('click.collapse.data-api', '[data-action^="accordion:"]', function(e) {
            const $elem = $(e.target);
            const action = $elem.data('action').slice(10);
            const method = {'expand-all': 'show', 'collapse-all': 'hide'}[action];
            const $target = $($elem.attr('data-target') || e.preventDefault() || $elem.attr('href'));
            $target.find('.collapse').collapse({toggle: false}).collapse(method);
        });
        $(document).on('shown.collapse.data-api hidden.collapse.data-api', '.accordion-body', function(e) {
            if (e.target === e.currentTarget) { // prevent processing if an event comes from child element
                const $toggle = $(e.target).closest('.accordion-group').find('[data-toggle=collapse]:first');
                $toggle.toggleClass('collapsed', e.type !== 'shown');
            }
        });
    });

    /* ============================================================
     * from height_fix.js
     * ============================================================ */
    // @TODO should be refactored in BAP-4020
    $(function() {
        let adjustHeight;

        const determineContext = context => {
            if (context instanceof $.Event || context instanceof Event) {
                context = context.target;
            }
            if (context instanceof HTMLElement) {
                return context;
            }
        };
        if (tools.isMobile()) {
            adjustHeight = context => {
                mediator.execute({name: 'responsive-layout:update', silent: true});
                mediator.trigger('layout:reposition', determineContext(context));
            };
        } else {
            adjustHeight = context => {
                mediator.execute({name: 'responsive-layout:update', silent: true});
                scrollspy.adjust();
                mediator.trigger('layout:reposition', determineContext(context));
            };
        }

        layout.onPageRendered(adjustHeight);

        $(window).on('resize', _.debounce(adjustHeight, 40));

        mediator.on('page:afterChange', adjustHeight);

        mediator.on('layout:adjustHeight', adjustHeight);
        mediator.on('datagrid:rendered datagrid_filters:rendered widget_remove', scrollspy.adjust);

        adjustHeight();
    });

    /* ============================================================
     * from form_buttons.js
     * ============================================================ */
    $(document).on('click', '.action-button', function() {
        const actionInput = $('input[name = "input_action"]');
        actionInput.val($(this).attr('data-action'));
    });

    /* ============================================================
     * from remove.confirm.js
     * ============================================================ */
    $(function() {
        $(document).on('click', '.remove-button', function(e) {
            const el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                const data = {
                    content: el.data('message')
                };

                const okText = el.data('ok-text');
                if (okText) {
                    data.okText = okText;
                }

                const title = el.data('title');
                if (title) {
                    data.title = title;
                }

                const cancelText = el.data('cancel-text');
                if (cancelText) {
                    data.cancelText = cancelText;
                }

                const confirm = new DeleteConfirmation(data);

                confirm.on('ok', function() {
                    mediator.execute('showLoading');

                    $.ajax({
                        url: el.data('url'),
                        type: 'DELETE',
                        success: function(data) {
                            el.trigger('removesuccess');
                            const redirectTo = el.data('redirect');
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

    /**
     * Gererates HTML for new rows to oro collection
     *
     * @param {jQuery} $listContainer
     * @param {number} [rowsCount]
     * @return {string}
     */

    const generateOroCollectionRows = function($listContainer, rowsCount) {
        rowsCount = rowsCount || 1;

        let lastIndex = -1;
        const $items = $listContainer.children('[data-content]');

        if ($items.length > 0) {
            const indexes = $items.toArray().map(function(item) {
                const selector = '[data-content]:not([data-content=""])';
                const content = $(item).find(selector).addBack(selector).attr('data-content');

                if (_.isEmpty(content)) {
                    return -1;
                }

                // Since `data-content` attribute can contain as full field name with index
                // as plain index it needs to cover both cases
                const matches = content.match(/\[(\d+)\]$/) || content.match(/^(\d+)$/);

                return matches ? Number(matches[1]) : -1;
            });

            lastIndex = _.max(indexes);
        }

        let rowsHTML = '';
        const prototypeName = $listContainer.attr('data-prototype-name') || '__name__';
        const prototypeHtml = $listContainer.attr('data-prototype');

        for (let i = 1; i <= rowsCount; i++) {
            rowsHTML += prototypeHtml.replace(new RegExp(prototypeName, 'g'), lastIndex + i);
        }

        return rowsHTML;
    };

    const validateContainer = function($container) {
        const $validationField = $container.find('[data-name="collection-validation"]:first');
        const $form = $validationField.closest('form');
        if ($form.data('validator')) {
            $form.validate().element($validationField.get(0));
        }
    };

    $(document).on('click add-rows', '.add-list-item', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }
        const containerSelector = $(this).data('container') || '.collection-fields-list';
        const $listContainer = $(this).closest('.row-oro').find(containerSelector).first();
        let rowCountAdd = 1;
        if ($(this).data('row-add-only-one')) {
            $(this).removeData('row-add-only-one');
        } else {
            rowCountAdd = e.count || $(containerSelector).data('row-count-add') || 1;
        }

        const rowsHtml = generateOroCollectionRows($listContainer, rowCountAdd);
        const {htmlProcessor = html => html} = e;
        $listContainer.append(htmlProcessor(rowsHtml)).trigger('content:changed');

        $listContainer.find('input.position-input').each((i, el) => $(el).val(i));

        if ($(this).data('validate-collection') !== false) {
            validateContainer($listContainer);
        }
    });

    $(document).on('click', '.addAfterRow', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }
        const $item = $(this).closest('.row-oro').parent();
        const $listContainer = $item.parent();
        const nextItemHtml = generateOroCollectionRows($listContainer, 1);

        $item.after(nextItemHtml);
        $listContainer.trigger('content:changed');

        $listContainer.find('input.position-input').each(function(i, el) {
            $(el).val(i);
        });
    });

    $(document).on('click', '.removeRow', function(e) {
        e.preventDefault();
        if ($(this).attr('disabled')) {
            return;
        }

        let closest = '*[data-content]';
        if ($(this).data('closest')) {
            closest = $(this).data('closest');
        }

        const item = $(this).closest(closest);
        item.trigger('content:remove')
            .remove();
    });

    /**
     * Support for [data-focusable] attribute
     */
    $(document).on('click', 'label[for]', function(e) {
        const forAttribute = $(e.target).attr('for');
        const labelForElement = $('#' + forAttribute + ':first');
        if (labelForElement.is('[data-focusable]')) {
            e.preventDefault();
            labelForElement.trigger('set-focus');
        }
    });
});
