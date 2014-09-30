/*jshint browser: true*/
/*jslint browser: true, nomen: true, vars: true*/
/*global require*/

require(['oroui/js/mediator'], function (mediator) {
    'use strict';
    mediator.once('page:afterChange', function () {
        //@TODO remove delay, when afterChange event will
        // take in account rendering from inline scripts
        setTimeout(function () {
            // emulates 'document ready state' for selenium tests
            document['page-rendered'] = true;
            mediator.trigger('page-rendered');
        }, 50);
    });
});

require(['jquery', 'underscore', 'orotranslation/js/translator', 'oroui/js/tools',
        'oroui/js/mediator', 'oroui/js/layout',
        'oroui/js/delete-confirmation', 'oroui/js/scrollspy',
        'bootstrap', 'jquery-ui', 'jquery-ui-timepicker'
    ], function ($, _, __, tools, mediator, layout, DeleteConfirmation, scrollspy) {
    'use strict';

    /* ============================================================
     * from layout.js
     * ============================================================ */
    $(function () {
        var $pageTitle = $('#page-title');
        if ($pageTitle.size()) {
            document.title = $('<div.>').html($('#page-title').text()).text();
        }
        layout.hideProgressBar();

        /* side bar functionality */
        $('div.side-nav').each(function () {
            var myParent = $(this),
                myParentHolder = $(myParent).parent().height() - 18;
            $(myParent).height(myParentHolder);
            /* open close bar */
            $(this).find("span.maximize-bar").click(function () {
                if (($(myParent).hasClass("side-nav-open")) || ($(myParent).hasClass("side-nav-locked"))) {
                    $(myParent).removeClass("side-nav-locked side-nav-open");
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').removeClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').removeClass('right-locked');
                    }
                    $(myParent).find('.bar-tools').css({
                        "height": "auto",
                        "overflow" : "visible"
                    });
                } else {
                    $(myParent).addClass("side-nav-open");
                    var openBarHeight = $("div.page-container").height() - 20,
                        testBarScroll = $(myParent).find('.bar-tools').height();
                    /* minus top-padding and bottom-padding */
                    $(myParent).height(openBarHeight);
                    if (openBarHeight < testBarScroll) {
                        $(myParent).find('.bar-tools').height((openBarHeight - 20)).css({
                            "overflow" : "auto"
                        });
                    }
                }
            });

            /* lock&unlock bar */
            $(this).find("span.lock-bar").click(function () {
                if ($(this).hasClass("lock-bar-locked")) {
                    $(myParent).addClass("side-nav-open")
                        .removeClass("side-nav-locked");
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').removeClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').removeClass('right-locked');
                    }
                } else {
                    $(myParent).addClass("side-nav-locked")
                        .removeClass("side-nav-open");
                    if ($(myParent).hasClass('left-panel')) {
                        $(myParent).parent('div.page-container').addClass('left-locked');
                    } else {
                        $(myParent).parent('div.page-container').addClass('right-locked');
                    }

                }
                $(this).toggleClass('lock-bar-locked');
            });

            /* open&close popup for bar items when bar is minimized. */
            $(this).find('.bar-tools li').each(function () {
                var myItem = $(this);
                $(myItem).find('.sn-opener').click(function () {
                    $(myItem).find("div.nav-box").fadeToggle("slow");

                    var $barOverlay   = $('#bar-drop-overlay'),
                        $page         = $('#page'),
                        overlayHeight = $page.height(),
                        overlayWidth  = $page.children('.wrapper').width();
                    $barOverlay.width(overlayWidth).height(overlayHeight);
                    $barOverlay.toggleClass('bar-open-overlay');
                });
                $(myItem).find("span.close").click(function () {
                    $(myItem).find("div.nav-box").fadeToggle("slow");
                    $('#bar-drop-overlay').toggleClass('bar-open-overlay');
                });
                $('#bar-drop-overlay').on({
                    click: function () {
                        $(myItem).find("div.nav-box").animate({
                            opacity: 0,
                            display: 'none'
                        }, function () {
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
            $(myParent).find('ul.bar-tools > li').each(function () {
                var _barLi = $(this);
                $(_barLi).find('span.open-bar-item').click(function () {
                    $(_barLi).find('div.nav-content').slideToggle();
                    $(_barLi).toggleClass('open-item');
                });
            });
        });

        /* ============================================================
         * Oro Dropdown close prevent
         * ============================================================ */
        var dropdownToggles = $('.oro-dropdown-toggle');
        dropdownToggles.click(function (e) {
            var $parent = $(this).parent().toggleClass('open');
            if ($parent.hasClass('open')) {
                $parent.find('.dropdown-menu').focus();
                $parent.find('input[type=text]').first().focus().select();
            }
        });
        $(document).on('focus.dropdown.data-api', '[data-toggle=dropdown]', _.debounce(function (e) {
            $(e.target).parent().find('input[type=text]').first().focus();
        }, 10));

        $(document).on('keyup.dropdown.data-api', '.dropdown-menu', function (e) {
            if (e.keyCode === 27) {
                $(e.currentTarget).parent().removeClass('open');
            }
        });

        // fixes submit by enter key press on select element
        $(document).on('keydown', 'form select', function(e) {
            if (e.keyCode === 13) {
                $(e.target.form).submit();
            }
        });

        $(document).on('focus', '.select2-focusser, .select2-input', function (e) {
            $('.hasDatepicker').datepicker('hide')
        });

        var openDropdownsSelector = '.dropdown.open, .dropdown .open, .oro-drop.open, .oro-drop .open';
        $('html').click(function (e) {
            var $target = $(e.target),
                clickingTarget = null;
            if ($target.hasClass('dropdown') || $target.hasClass('oro-drop')) {
                clickingTarget = $target;
            } else {
                clickingTarget = $target.closest('.dropdown, .oro-drop');
            }
            $(openDropdownsSelector).not(clickingTarget).removeClass('open');
        });

        $('#main-menu').mouseover(function () {
            $(openDropdownsSelector).removeClass('open');
        });

        mediator.on('page:beforeChange', function () {
            $('.pin-menus.dropdown.open, .nav .dropdown.open').removeClass('open');
            $('.dropdown:hover > .dropdown-menu').hide().addClass('manually-hidden');
        });
        mediator.on('page:afterChange', function() {
            $('.dropdown .dropdown-menu.manually-hidden').css('display', '');
        });

        // fix + extend bootstrap.collapse functionality
        $(document).on('click.collapse.data-api', '[data-action^="accordion:"]', function (e) {
            var $elem = $(e.target),
                action = $elem.data('action').slice(10),
                method = {'expand-all': 'show', 'collapse-all': 'hide'}[action],
                $target = $($elem.attr('data-target') || e.preventDefault() || $elem.attr('href'));
            $target.find('.collapse').collapse({toggle: false}).collapse(method);
        });
        $(document).on('shown.collapse.data-api hidden.collapse.data-api', '.collapse', function (e) {
            var $toggle = $(e.target).closest('.accordion-group').find('[data-toggle=collapse]').first();
            $toggle[e.type === 'shown' ? 'removeClass' : 'addClass']('collapsed');
        });
    });

    /* ============================================================
     * from height_fix.js
     * ============================================================ */
    (function () {
        if (tools.isMobile()) {
            return;
        }
        /* dynamic height for central column */
        var anchor = $('#bottom-anchor'),
            content = false;

        var initializeContent = function () {
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
        var adjustHeight = function () {
            initializeContent();

            // set width for #main container
            $main.width($topPage.width() - $leftPanel.width() - $rightPanel.width());

            var debugBarHeight = $('.sf-toolbar:visible').height() || 0;
            var anchorTop = anchor.position().top;
            var footerHeight = $('#footer:visible').height() || 0;
            var fixContent = 1;

            $(content.get().reverse()).each(function (pos, el) {
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

        if ($('.sf-toolbar').length) {
            adjustHeight = (function () {
                var orig = adjustHeight;
                var waitForDebugBar = function (attempt) {
                    if ($('.sf-toolbar').children().length) {
                        $('body').addClass('dev-mode');
                        _.delay(orig, 10);
                    } else if (attempt < 100) {
                        _.delay(waitForDebugBar, 500, attempt + 1);
                    }
                };

                return _.wrap(adjustHeight, function (orig) {
                    $('body').removeClass('dev-mode');
                    orig();
                    waitForDebugBar(0);
                });
            }());
        }

        var adjustReloaded = function () {
            content = false;
            adjustHeight();
        };

        layout.onPageRendered(adjustHeight);

        $(window).on('resize', adjustHeight);

        mediator.on("page:afterChange", adjustReloaded);

        mediator.on('layout:adjustReloaded', adjustReloaded);
        mediator.on('layout:adjustHeight', adjustHeight);
        mediator.on('datagrid:rendered datagrid_filters:rendered', scrollspy.adjust);

        $(function () {
            adjustHeight();
        });
    }());

    /* ============================================================
     * from form_buttons.js
     * ============================================================ */
    $(document).on('click', '.action-button', function () {
        var actionInput = $('input[name = "input_action"]');
        actionInput.val($(this).attr('data-action'));
        $('#' + actionInput.attr('data-form-id')).submit();
    });

    /* ============================================================
     * from remove.confirm.js
     * ============================================================ */
    $(function () {
        $(document).on('click', '.remove-button', function (e) {
            var el = $(this);
            if (!(el.is('[disabled]') || el.hasClass('disabled'))) {
                var confirm,
                    message = el.data('message');

                confirm = new DeleteConfirmation({
                    content: message
                });

                confirm.on('ok', function () {
                    mediator.execute('showLoading');

                    $.ajax({
                        url: el.data('url'),
                        type: 'DELETE',
                        success: function (data) {
                            el.trigger('removesuccess');
                            mediator.execute('addMessage', 'success', el.data('success-message'));
                            if (el.data('redirect')) {
                                mediator.execute('redirectTo', {url: el.data('redirect')});
                            } else {
                                mediator.execute('hideLoading');
                            }
                        },
                        error: function () {
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
    $(document).on('click', '.add-list-item', function (e) {
        e.preventDefault();
        var cList  = $(this).siblings('.collection-fields-list'),
            widget = cList.attr('data-prototype').replace(/__name__/g, cList.children().length),
            data = $('<div/>').html(widget);

        data.children().appendTo(cList);
        /* temporary solution need add init only for new created row */
        layout.styleForm(cList);
        /* temporary solution finish */
    });

    $(document).on('click', '.removeRow', function (e) {
        e.preventDefault();
        $(this).parents('*[data-content]').remove();
    });
});
