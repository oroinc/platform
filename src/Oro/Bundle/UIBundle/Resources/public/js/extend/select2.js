define(['jquery', 'underscore', 'orotranslation/js/translator', 'jquery.select2'], function($, _, __, Select2) {
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
        // jshint -W040
        var opts = this.opts;
        var id = opts.id;
        var parent = container.parent();
        var selection = this.val();

        var populate = function(results, container, depth, parentStack) {
            // jscs:disable
            var i, l, result, selectable, disabled, compound, node, label, innerContainer,
                formatted, subId, parent, resultId;
            // jscs:enable
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
                    $.each(parentStack, function() {
                        this.addClass('in');
                    });
                }

                node.prepend(label);
                node.data('select2-data', result);
                container.append(node);
            }
        };

        parent.attr('id', parent.attr('id') || ('select2container_' + Date.now()));
        container.on('click.collapse.data-api', '[data-toggle=collapse]', function(e) {
            var $this = $(this);
            var target = $this.attr('data-target');
            var option = $(target).data('collapse') ? 'toggle' : $this.data();
            $this[$(target).hasClass('in') ? 'addClass' : 'removeClass']('collapsed');
            $(target).collapse(option);
        });
        populate(results, container, 0, []);
    }
    var overrideMethods = {
        processResult: function(original, data) {
            original.apply(this, _.rest(arguments));
            var results = _.result(data, 'results') || [];
            if (results.length > 0 && this.opts.dontSelectFirstOptionOnOpen) {
                this.results.find('.select2-highlighted').removeClass('select2-highlighted');
                this.dropdown.add(this.search).one('keydown', _.bind(function() {
                    delete this.opts.dontSelectFirstOptionOnOpen;
                }, this));
            }
        },
        moveHighlight: function(original) {
            if (this.highlight() === -1) {
                this.highlight(0);
            } else {
                original.apply(this, _.rest(arguments));
            }
        }
    };

    // Override methods of AbstractSelect2 class
    (function(prototype) {
        var select2DropBelowClassName = 'select2-drop-below';
        var positionDropdown = prototype.positionDropdown;
        var close = prototype.close;
        var prepareOpts = prototype.prepareOpts;
        var init = prototype.init;
        prototype.prepareOpts = function(options) {
            if (options.collapsibleResults) {
                options.populateResults = populateCollapsibleResults;
                var matcher = options.matcher || $.fn.select2.defaults.matcher;
                options.matcher = function(term, text, option) {
                    return !option.children && matcher.apply(this, arguments);
                };
            }

            var additionalRequestParams = options.element.data('select2_query_additional_params');
            if (additionalRequestParams && options.ajax !== undefined) {
                options.ajax.url += (options.ajax.url.indexOf('?') < 0 ? '?' : '&') + $.param(additionalRequestParams);
            }

            return prepareOpts.call(this, options);
        };

        prototype.positionDropdown = function() {
            var $container = this.container;
            positionDropdown.apply(this, arguments);
            var dialogIsBelow = $container.hasClass('select2-dropdown-open') &&
                !$container.hasClass('select2-drop-above');
            $container.parent().toggleClass(select2DropBelowClassName, dialogIsBelow);
        };

        prototype.close = function() {
            close.apply(this, arguments);
            this.container.parent().removeClass(select2DropBelowClassName);
        };

        prototype.init = function() {
            init.apply(this, arguments);
            this.breadcrumbs = $('<ul class="select2-breadcrumbs"></ul>');
            this.breadcrumbs.on('click', '.select2-breadcrumb-item', $.proxy(function(e) {
                var data = $(e.currentTarget).data('select2-data');
                this.pagePath = data.pagePath;
                this.search.val('');
                this.updateResults();
                e.stopPropagation();
            }, this));
            this.dropdown.prepend(this.breadcrumbs);
        };

        prototype.updateBreadcrumbs = function() {
            var breadcrumbs = this.breadcrumbs;
            var opts = this.opts;
            breadcrumbs.empty();
            if ($.isFunction(opts.formatBreadcrumbItem) && $.isFunction(opts.breadcrumbs)) {
                var items = opts.breadcrumbs(this.pagePath);
                $.each(items, function(i, item) {
                    var $item = opts.formatBreadcrumbItem(item, {index: i, length: items.length});
                    $item = $('<li class="select2-breadcrumb-item">' + $item + '</li>');
                    $item.data('select2-data', {pagePath: item.pagePath});
                    breadcrumbs.append($item);
                });
            }
        };

    }(Select2['class'].abstract.prototype));

    (function(prototype) {
        var onSelect = prototype.onSelect;
        var updateResults = prototype.updateResults;
        var clear = prototype.clear;

        prototype.onSelect = function(data, options) {
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

        prototype.updateResults = function(initial) {
            updateResults.apply(this, arguments);
            if (initial === true && this.opts.element.val()) {
                this.pagePath = this.opts.element.val();
            }
            this.updateBreadcrumbs();
            this.positionDropdown();
        };

        prototype.clear = function() {
            this.pagePath = '';
            clear.apply(this, arguments);
        };

        prototype.postprocessResults = _.wrap(prototype.postprocessResults, overrideMethods.processResult);

        prototype.moveHighlight = _.wrap(prototype.moveHighlight, overrideMethods.moveHighlight);

    }(Select2['class'].single.prototype));

    // Override methods of MultiSelect2 class
    // Fix is valid for version 3.4.1
    (function(prototype) {
        function killEvent(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function indexOf(value, array) {
            var i = 0;
            var l = array.length;
            for (; i < l; i = i + 1) {
                if (equal(value, array[i])) {
                    return i;
                }
            }
            return -1;
        }

        function equal(a, b) {
            if (a === b) {
                return true;
            }
            if (a === undefined || b === undefined) {
                return false;
            }
            if (a === null || b === null) {
                return false;
            }
            // Check whether 'a' or 'b' is a string (primitive or object).
            // The concatenation of an empty string (+'') converts its argument to a string's primitive.
            if (a.constructor === String) {
                return a + '' === b + '';
            }
            if (b.constructor === String) {
                return b + '' === a + '';
            }
            return false;
        }

        var resizeSearch = prototype.resizeSearch;

        prototype.resizeSearch = function() {
            this.selection.addClass('select2-search-resize');
            resizeSearch.apply(this, arguments);
            this.selection.removeClass('select2-search-resize');
            this.search.width(Math.floor($(this.search).width()) - 1);
        };

        prototype.updateSelection = function(data) {
            var ids = [];
            var filtered = [];
            var self = this;

            // filter out duplicates
            $(data).each(function() {
                if (indexOf(self.id(this), ids) < 0) {
                    ids.push(self.id(this));
                    filtered.push(this);
                }
            });
            data = filtered;

            this.selection.find('.select2-search-choice').remove();
            var val = this.getVal();
            $(data).each(function() {
                self.addSelectedChoiceOptimized(this, val);
            });
            this.setVal(val);
            self.postprocessResults();
        };

        /**
         * Makes it possible to render multiselect with 10 000 selected business units
         */
        prototype.addSelectedChoiceOptimized = function(data, val) {
            var enableChoice = !data.locked;
            var enabledItem = $(
                    '<li class=\'select2-search-choice\'>' +
                    '    <div></div>' +
                    '    <a href=\'#\' onclick=\'return false;\' ' +
                    'class=\'select2-search-choice-close\' tabindex=\'-1\'></a>' +
                    '</li>');
            var disabledItem = $(
                    '<li class=\'select2-search-choice select2-locked\'>' +
                    '<div></div>' +
                    '</li>');
            var choice = enableChoice ? enabledItem : disabledItem;
            var id = this.id(data);
            var formatted;

            formatted = this.opts.formatSelection(data, choice.find('div'), this.opts.escapeMarkup);
            /* jshint ignore:start */
            if (formatted != undefined) {
                choice.find('div').replaceWith('<div>' + formatted + '</div>');
            }
            var cssClass = this.opts.formatSelectionCssClass(data, choice.find('div'));
            if (cssClass != undefined) {
                choice.addClass(cssClass);
            }
            /* jshint ignore:end */

            if (enableChoice) {
                choice.find('.select2-search-choice-close')
                    .on('mousedown', killEvent)
                    .on('click dblclick', this.bind(function(e) {
                    if (!this.isInterfaceEnabled()) {
                        return;
                    }

                    $(e.target).closest('.select2-search-choice').fadeOut('fast', this.bind(function() {
                        this.unselect($(e.target));
                        this.selection.find('.select2-search-choice-focus').removeClass('select2-search-choice-focus');
                        this.close();
                        this.focusSearch();
                    })).dequeue();
                    killEvent(e);
                })).on('focus', this.bind(function() {
                    if (!this.isInterfaceEnabled()) {
                        return;
                    }
                    this.container.addClass('select2-container-active');
                    this.dropdown.addClass('select2-drop-active');
                }));
            }

            choice.data('select2-data', data);
            choice.insertBefore(this.searchContainer);

            val.push(id);
        };

        prototype.postprocessResults = _.wrap(prototype.postprocessResults, overrideMethods.processResult);

        prototype.moveHighlight = _.wrap(prototype.moveHighlight, overrideMethods.moveHighlight);

    }(Select2['class'].multi.prototype));

    $.fn.select2.defaults = $.extend($.fn.select2.defaults, {
        formatSearching: function() { return __('Searching...'); },
        formatNoMatches: function() { return __('No matches found'); }
    });
});
