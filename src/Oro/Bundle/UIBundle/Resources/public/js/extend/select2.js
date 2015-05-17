/*jshint browser:true, nomen:true*/
/*jslint browser:true, nomen:true*/
/*global define*/
define(['jquery', 'orotranslation/js/translator', 'jquery.select2'], function ($, __, Select2) {
    'use strict';

    /**
     * An overload of populateResults method,
     * renders search results with collapsible groups
     *
     * @param {jQuery} container Dropdown container in jQuery object
     * @param {Object} results List of search result items
     * @param {Object} query Searched term
     * @this AbstractSelect2
     */
    function populateCollapsibleResults(container, results, query) {
        /*jshint validthis:true */
        var populate, data, result, children,
            opts = this.opts,
            id = opts.id,
            parent = container.parent(),
            selection = this.val();

        populate = function (results, container, depth, parentStack) {
            var i, l, result, selectable, disabled, compound, node, label, innerContainer,
                formatted, subId, parent, resultId;
            results = opts.sortResults(results, container, query);
            parent = container.parent();

            for (i = 0, l = results.length; i < l; i = i + 1) {
                result = results[i];
                resultId = result.id;

                disabled = (result.disabled === true);
                selectable = (!disabled) && (id(result) !== undefined);
                compound = result.children && result.children.length > 0;

                node = $('<li></li>')
                    .addClass('select2-result')
                    .addClass('select2-results-dept-' + depth)
                    .addClass(selectable ? 'select2-result-selectable' : 'select2-result-unselectable')
                    .addClass(opts.formatResultCssClass(result));
                if (disabled) {
                    node.addClass('select2-disabled');
                }
                if (compound) {
                    node.addClass('select2-result-with-children');
                }

                label = $('<div></div>');
                label.addClass('select2-result-label');

                formatted = opts.formatResult(result, label, query, opts.escapeMarkup);
                if (formatted !== undefined) {
                    label.html(formatted);
                }

                if (compound) {
                    container.addClass('accordion');
                    subId = parent.attr('id') + '_' + depth + '_' + i;

                    innerContainer = $('<ul></ul>')
                        .addClass('select2-result-sub')
                        .wrap('<div class="accordion-body collapse" id="' + subId + '" />');
                    populate(result.children, innerContainer, depth + 1, parentStack.concat(innerContainer.parent()));
                    innerContainer = innerContainer.parent();

                    node.addClass('accordion-group')
                        .append(innerContainer);

                    if (query.term) {
                        innerContainer.addClass('in');
                    } else {
                        label.addClass('collapsed');
                    }

                    label = label.addClass('accordion-toggle')
                        .attr('data-toggle', 'collapse')
                        .attr('data-target', '#' + subId)
                        .attr('data-parent', '#' + parent.attr('id'))
                        .wrap('<div class="accordion-heading"/>')
                        .parent();
                }

                if (selection.indexOf(resultId) >= 0) {
                    $.each(parentStack, function () {
                        this.addClass('in');
                    });
                }

                node.prepend(label);
                node.data('select2-data', result);
                container.append(node);
            }
        };

        parent.attr('id', parent.attr('id') || ('select2container_' + Date.now()));
        container.on('click.collapse.data-api', '[data-toggle=collapse]', function (e) {
            var $this = $(this),
                target = $this.attr('data-target'),
                option = $(target).data('collapse') ? 'toggle' : $this.data();
            $this[$(target).hasClass('in') ? 'addClass' : 'removeClass']('collapsed');
            $(target).collapse(option);
        });
        populate(results, container, 0, []);
    }

    // Override methods of AbstractSelect2 class
    (function (prototype) {
        var select2DropBelowClassName = 'select2-drop-below',
            positionDropdown = prototype.positionDropdown,
            close = prototype.close,
            prepareOpts = prototype.prepareOpts,
            init = prototype.init;
        prototype.prepareOpts = function (options) {
            if (options.collapsibleResults) {
                options.populateResults = populateCollapsibleResults;
                var matcher = options.matcher || $.fn.select2.defaults.matcher;
                options.matcher = function (term, text, option) {
                    return !option.children && matcher.apply(this, arguments);
                };
            }

            var additionalRequestParams = options.element.data('select2_query_additional_params');
            if (additionalRequestParams && options.ajax !== undefined) {
                options.ajax.url += (options.ajax.url.indexOf('?') < 0 ? '?' : '&') + $.param(additionalRequestParams);
            }

            return prepareOpts.call(this, options);
        };

        prototype.positionDropdown = function(){
            var dialogIsBelow,
                $container = this.container;
            positionDropdown.apply(this, arguments);
            dialogIsBelow = $container.hasClass('select2-dropdown-open') && !$container.hasClass('select2-drop-above');
            $container.parent().toggleClass(select2DropBelowClassName, dialogIsBelow);
        };

        prototype.close = function(){
            close.apply(this, arguments);
            this.container.parent().removeClass(select2DropBelowClassName);
        };

        prototype.init = function () {
            init.apply(this, arguments);
            this.breadcrumbs = $('<ul class="select2-breadcrumbs"></ul>');
            this.breadcrumbs.on('click', '.select2-breadcrumb-item', $.proxy(function (e) {
                var data = $(e.currentTarget).data('select2-data');
                this.pagePath = data.pagePath;
                this.search.val('');
                this.updateResults();
                e.stopPropagation();
            }, this));
            this.dropdown.prepend(this.breadcrumbs);
        };

        prototype.updateBreadcrumbs = function () {
            var breadcrumbs = this.breadcrumbs,
                opts = this.opts;
            breadcrumbs.empty();
            if ($.isFunction(opts.formatBreadcrumbItem) && $.isFunction(opts.breadcrumbs)) {
                var items = opts.breadcrumbs(this.pagePath);
                $.each(items, function (i, item) {
                    var $item = opts.formatBreadcrumbItem(item, {index: i, length: items.length});
                    $item = $("<li class='select2-breadcrumb-item'>" + $item + "</li>");
                    $item.data('select2-data', {pagePath: item.pagePath});
                    breadcrumbs.append($item);
                });
            }
        };
    }(Select2['class'].abstract.prototype));

    (function (prototype) {
        var onSelect = prototype.onSelect;
        var updateResults = prototype.updateResults;
        var clear = prototype.clear;

        prototype.onSelect = function (data, options) {
            if (data.id === undefined && data.pagePath) {
                this.pagePath = data.pagePath;
                this.search.val('');
                this.updateResults();
                return;
            }

            onSelect.apply(this, arguments);

            // @todo BAP-3928, remove this method override after upgrade select2 to v3.4.6, fix code is taken from there
            if ((!options || !options.noFocus) && this.opts.minimumResultsForSearch >= 0) {
                this.focusser.focus();
            }
        };

        prototype.updateResults = function (initial) {
            updateResults.apply(this, arguments);
            if (initial === true && this.opts.element.val()) {
                this.pagePath = this.opts.element.val();
            }
            this.updateBreadcrumbs();
            this.positionDropdown();
        };

        prototype.clear = function () {
            this.pagePath = '';
            clear.apply(this, arguments);
        };
    }(Select2['class'].single.prototype));

    // Override methods of MultiSelect2 class
    // Fix is valid for version 3.4.1
    (function(prototype) {
        var resizeSearch = prototype.resizeSearch;

        prototype.resizeSearch = function() {
            resizeSearch.apply(this, arguments);
            this.search.width(Math.floor($(this.search).width()) - 1);
        };
    }(Select2['class'].multi.prototype));

    $.fn.select2.defaults = $.extend($.fn.select2.defaults, {
        formatSearching: function() { return __('Searching...'); },
        formatNoMatches: function () { return __('No matches found'); }
    });
});
