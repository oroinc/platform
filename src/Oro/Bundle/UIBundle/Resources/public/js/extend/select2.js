define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Select2 = require('jquery.select2');
    const tools = require('oroui/js/tools');

    require('oroui/js/select2-l10n');

    // disable scroll on IOS when select2 drop is visible
    if (tools.isIOS()) {
        $(document).on('wheel mousewheel touchmove keydown', '#select2-drop-mask', function(e) {
            e.preventDefault();
        });
    }

    $('body').on('click', function(e) {
        // Fixes issue with extra click event in Safari browser. It triggers click event on body, even though
        // preceding mousedown and mouseup events had different targets, example https://jsfiddle.net/bxm79u8o/27/
        // Select2 opens its dropdown on mousedown event. With the dropdown opening, it adds transparent mask under it.
        // And mouseup event is triggered on that mask.
        if (e.target === e.currentTarget) {
            e.stopPropagation();
        }
    });

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
        const opts = this.opts;
        const id = opts.id;
        const parent = container.parent();
        const selection = this.val();

        const populate = function(results, container, depth, parentStack) {
            let i;
            let l;
            let result;
            let selectable;
            let disabled;
            let compound;
            let node;
            let label;
            let innerContainer;
            let formatted;
            let labelId;
            let subId;
            let resultId;
            let expanded;

            results = opts.sortResults(results, container, query);
            const parent = container.parent();

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
                    labelId = _.uniqueId('label-');
                    subId = parent.attr('id') + '_' + depth + '_' + i;

                    expanded = Boolean(query.term);

                    innerContainer = $('<div class="accordion-body collapse"/>')
                        .toggleClass('show', expanded)
                        .attr({
                            'id': subId,
                            'role': 'subtree',
                            'aria-labelledby': labelId
                        })
                        .append('<ul class="select2-result-sub"/>');

                    populate(result.children, innerContainer.children(), depth + 1, parentStack.concat(innerContainer));

                    node.addClass('accordion-group')
                        .append(innerContainer);

                    label.addClass('accordion-toggle')
                        .toggleClass('collapsed', !expanded)
                        .attr({
                            'id': labelId,
                            'data-toggle': 'collapse',
                            'data-target': '#' + subId,
                            'data-parent': '#' + parent.attr('id'),
                            'aria-controls': subId,
                            'aria-expanded': expanded
                        });
                    label = $('<div class="accordion-heading"/>').append(label);
                }

                if (selection.indexOf(resultId) >= 0) {
                    $.each(parentStack, function() {
                        this.addClass('show');
                    });
                }

                node.prepend(label);
                node.data('select2-data', result);
                container.append(node);
            }
        };

        if (!parent.attr('id')) {
            parent.attr('id', _.uniqueId('select2container_'));
        }

        container.on('click.collapse.data-api', '[data-toggle=collapse]', function(e) {
            const $el = $(e.currentTarget);
            const $target = $($el.attr('data-target'));
            const options = $target.data('bs.collapse') ? 'toggle' : $el.data();

            $el.toggleClass('collapsed', $target.hasClass('show'));
            $target.collapse(options);
        });
        populate(results, container, 0, []);
    }
    const overrideMethods = {
        processResult: function(original, data, ...rest) {
            original.call(this, data, ...rest);
            const results = _.result(data, 'results') || [];
            if (results.length > 0 && this.opts.dontSelectFirstOptionOnOpen) {
                this.results.find('.select2-highlighted').removeClass('select2-highlighted');
                this.dropdown.add(this.search).one('keydown', _.bind(function() {
                    delete this.opts.dontSelectFirstOptionOnOpen;
                }, this));
            }
        },
        moveHighlight: function(original, ...rest) {
            if (this.highlight() === -1) {
                this.highlight(0);
            } else {
                original.apply(this, rest);
            }
        },
        initContainer: function(original, ...rest) {
            original.apply(this, rest);

            this.focusser.off('keyup-change input');
            this.focusser.on('keyup-change input', this.bind(function(e) {
                const showSearch = this.results[0].children.length >= this.opts.minimumResultsForSearch;

                if (showSearch) {
                    e.stopPropagation();
                    if (this.opened()) {
                        return;
                    }
                    this.open();
                } else {
                    this.clearSearch();
                }
            }));
        },
        tokenize: function(original) {
            const opts = this.opts;
            const search = this.search;
            const results = this.results;
            if (opts.allowCreateNew && opts.createSearchChoice) {
                const def = opts.createSearchChoice.call(this, search.val(), []);
                if (def !== void 0 && def !== null && this.id(def) !== void 0 && this.id(def) !== null) {
                    results.empty();
                    if (search.val()) {
                        opts.populateResults.call(this, results, [def], {
                            term: search.val(),
                            page: this.resultsPage,
                            context: null
                        });
                        this.highlight(0);
                    }
                    if (opts.formatSearching) {
                        results.append('<li class="select2-searching">' + opts.formatSearching() + '</li>');
                    }
                    search.removeClass('select2-active');
                    this.positionDropdown();
                }
            }
            original.call(this);
        },

        /* eslint-disable */
        // Overridden full onSelect method for multi chooses,
        // this solution related to https://github.com/select2/select2/issues/1513
        onSelect: function (data, options) {

            if (!this.triggerSelect(data)) { return; }

            this.addSelectedChoice(data);

            this.opts.element.trigger({ type: "selected", val: this.id(data), choice: data });

            if (this.select || !this.opts.closeOnSelect) this.postprocessResults(data, false, this.opts.closeOnSelect===true);

            if (this.opts.closeOnSelect) {
                this.close();
                this.search.width(10);
            } else {
                if (this.countSelectableResults()>0) {
                    this.search.width(10);
                    this.resizeSearch();
                    if (this.getMaximumSelectionSize() > 0 && this.val().length >= this.getMaximumSelectionSize()) {
                        // if we reached max selection size repaint the results so choices
                        // are replaced with the max selection reached message
                        this.updateResults(true);
                    }
                    this.positionDropdown();
                } else {
                    // if nothing left to select close
                    this.close();
                    this.search.width(10);
                }
            }

            // since its not possible to select an element that has already been
            // added we do not need to check if this is a new element before firing change
            this.triggerChange({ added: data });

            if (!options || !options.noFocus)
                this.focusSearch();
        },
        /* eslint-enable */
        showSearch: function(original, showSearchInput) {
            original.call(this, showSearchInput);
            $(this.container).toggleClass('select2-container-with-searchbox', showSearchInput);
        }
    };

    // Override methods of AbstractSelect2 class
    (function(prototype) {
        const select2SearchName = _.uniqueId('select2searchname');
        const select2DropBelowClassName = 'select2-drop-below';
        const positionDropdown = prototype.positionDropdown;
        const close = prototype.close;
        const open = prototype.open;
        const prepareOpts = prototype.prepareOpts;
        const init = prototype.init;
        const destroy = prototype.destroy;

        prototype.dropdownFixedMode = false;

        prototype.prepareOpts = function(options) {
            if (options.collapsibleResults) {
                options.populateResults = populateCollapsibleResults;
                const matcher = options.matcher || $.fn.select2.defaults.matcher;
                options.matcher = function(term, text, option) {
                    return !option.children && matcher.call(this, term, text, option);
                };
            }

            const additionalRequestParams = options.element.data('select2_query_additional_params');
            if (additionalRequestParams && options.ajax !== undefined) {
                options.ajax.url += (options.ajax.url.indexOf('?') < 0 ? '?' : '&') + $.param(additionalRequestParams);
            }

            const preparedOptions = prepareOpts.call(this, options);
            const query = preparedOptions.query;

            preparedOptions.query = function(queryOptions, ...rest) {
                queryOptions.term = queryOptions.term && queryOptions.term.trim();
                return query.call(this, queryOptions, ...rest);
            };

            return preparedOptions;
        };

        prototype.positionDropdown = function() {
            const $container = this.container;
            positionDropdown.call(this);

            if (this.dropdownFixedMode) {
                let top = this.container[0].getBoundingClientRect().top;

                if (this.dropdown.hasClass('select2-drop-above')) {
                    top -= this.dropdown.height();
                } else {
                    top += this.container.outerHeight(false);
                }

                this.dropdown.css({position: 'fixed', top: top});
            } else if (this.dropdown.css('position') === 'fixed') {
                this.dropdown.css('position', '');
            }

            const dialogIsBelow = $container.hasClass('select2-dropdown-open') &&
                !$container.hasClass('select2-drop-above');
            if ($container.parent().hasClass(select2DropBelowClassName) !== dialogIsBelow) {
                $container.parent().toggleClass(select2DropBelowClassName, dialogIsBelow);
                this.opts.element.trigger('select2:dialogReposition', dialogIsBelow ? 'below' : 'top');
            }
        };

        prototype.open = function() {
            // Add unique name for select2 search for disabling auto-fill, auto-complete functions.
            this.search.attr('name', select2SearchName);
            return open.call(this);
        };

        prototype.close = function() {
            close.call(this);
            this.container.parent().removeClass(select2DropBelowClassName);
            // Remove previously auto generated name
            this.search.removeAttr('name');
        };

        prototype.init = function(opts) {
            init.call(this, opts);
            this.breadcrumbs = $('<ul class="select2-breadcrumbs"></ul>');
            this.breadcrumbs.on('click.select2', '.select2-breadcrumb-item', function(e) {
                const data = $(e.currentTarget).data('select2-data');
                this.pagePath = data.pagePath;
                this.search.val('');
                this.updateResults();
                e.stopPropagation();
            }.bind(this));
            this.dropdown.prepend(this.breadcrumbs);
            this.search
                .on('focus', function() {
                    // Add unique name for select2 search for disabling auto-fill, auto-complete functions.
                    this.search.attr('name', select2SearchName);
                }.bind(this))
                .on('blur', function() {
                    // Remove previously auto generated name
                    this.search.removeAttr('name');
                }.bind(this));

            this.opts.element.trigger($.Event('select2-init'));
        };

        prototype.destroy = function() {
            if (this.propertyObserver) {
                this.propertyObserver.disconnect();
                delete this.propertyObserver;
                this.propertyObserver = null;
            }
            this.breadcrumbs.off('.select2');
            // Remove previously auto generated name
            this.search.removeAttr('name');
            destroy.call(this);
        };

        prototype.updateBreadcrumbs = function() {
            const breadcrumbs = this.breadcrumbs;
            const opts = this.opts;
            breadcrumbs.empty();
            if (typeof opts.formatBreadcrumbItem === 'function' && typeof opts.breadcrumbs === 'function') {
                const items = opts.breadcrumbs(this.pagePath);
                $.each(items, function(i, item) {
                    const itemHTML = opts.formatBreadcrumbItem(item, {index: i, length: items.length});
                    const $item = $('<li class="select2-breadcrumb-item">' + itemHTML + '</li>');
                    $item.data('select2-data', {pagePath: item.pagePath});
                    breadcrumbs.append($item);
                });
            }
        };

        prototype.triggerChange = _.wrap(prototype.triggerChange, function(original, details) {
            details = details || {};
            if (this.changedManually) {
                details.manually = true;
            }
            original.call(this, details);
        });
    }(Select2['class'].abstract.prototype));

    (function(prototype) {
        const updateResults = prototype.updateResults;
        const clear = prototype.clear;
        const isPlaceholderOptionSelected = prototype.isPlaceholderOptionSelected;

        prototype.onSelect = _.wrap(prototype.onSelect, function(original, data, options) {
            if (data.id === undefined && data.pagePath) {
                this.pagePath = data.pagePath;
                this.search.val('');
                this.updateResults();
                return;
            }

            this.changedManually = true;
            original.call(this, data, options);
            delete this.changedManually;

            // @todo BAP-3928, remove this method override after upgrade select2 to v3.4.6, fix code is taken from there
            if ((!options || !options.noFocus) && this.opts.minimumResultsForSearch >= 0) {
                this.focusser.focus();
            }
        });

        // Overriding method to avoid bug with placeholder in version 3.4.1
        // see https://github.com/select2/select2/issues/1542
        // @todo remove after upgrade to version >= 3.4.2
        prototype.updateSelection = function(data) {
            const container = this.selection.find('.select2-chosen');
            let formatted;

            this.selection.data('select2-data', data);

            container.empty();
            if (data !== null && data !== []) {
                formatted = this.opts.formatSelection(data, container, this.opts.escapeMarkup);
            }
            if (formatted !== undefined) {
                container.append(formatted);
            }
            const cssClass = this.opts.formatSelectionCssClass(data, container);
            if (cssClass !== undefined) {
                container.addClass(cssClass);
            }

            this.selection.removeClass('select2-default');

            if (this.opts.allowClear && this.getPlaceholder() !== undefined) {
                this.container.addClass('select2-allowclear');
            }
        };

        // Overriding method to avoid bug with placeholder in version 3.4.1
        // see https://github.com/select2/select2/issues/1542
        // @todo remove after upgrade to version >= 3.4.2
        prototype.isPlaceholderOptionSelected = function() {
            if (!this.getPlaceholder()) {
                return false; // no placeholder specified so no option should be considered
            }

            return isPlaceholderOptionSelected.call(this);
        };

        prototype.updateResults = function(initial) {
            updateResults.call(this, initial);
            if (initial === true && this.opts.element.val()) {
                this.pagePath = this.opts.element.val();
            }
            this.updateBreadcrumbs();
            this.positionDropdown();
        };

        prototype.clear = function() {
            this.pagePath = '';
            clear.call(this);
        };

        prototype.postprocessResults = _.wrap(prototype.postprocessResults, overrideMethods.processResult);

        prototype.moveHighlight = _.wrap(prototype.moveHighlight, overrideMethods.moveHighlight);
        prototype.initContainer = _.wrap(prototype.initContainer, overrideMethods.initContainer);
        prototype.tokenize = _.wrap(prototype.tokenize, overrideMethods.tokenize);
        prototype.showSearch = _.wrap(prototype.showSearch, overrideMethods.showSearch);
    }(Select2['class'].single.prototype));

    // Override methods of MultiSelect2 class
    // Fix is valid for version 3.4.1
    (function(prototype) {
        function killEvent(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function indexOf(value, array) {
            let i = 0;
            const l = array.length;
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

        /* original private Select2 methods */
        /* eslint-disable */
        var sizer;

        function measureTextWidth(e) {
            if (!sizer){
                var style = e[0].currentStyle || window.getComputedStyle(e[0], null);
                sizer = $(document.createElement("div")).css({
                    position: "absolute",
                    left: "-10000px",
                    top: "-10000px",
                    display: "none",
                    fontSize: style.fontSize,
                    fontFamily: style.fontFamily,
                    fontStyle: style.fontStyle,
                    fontWeight: style.fontWeight,
                    letterSpacing: style.letterSpacing,
                    textTransform: style.textTransform,
                    whiteSpace: "nowrap"
                });
                sizer.attr("class","select2-sizer");
                $("body").append(sizer);
            }
            sizer.text(e.val());
            return sizer.width();
        }
        /* eslint-enable */
        /* original private Select2 methods:end */

        /**
         * Overrides select2 resizeSearch method.
         * Resizes search input to fill available width.
         * Returns rounded value.
         */
        prototype.resizeSearch = function() {
            //  Determines if search input item is on first row and sets correspondent css class.
            //  This class is needed for additional styling to prevent visual intersection with action buttons
            const isFirstRow = (
                this.selection.children(':first-child').position().top ===
                this.searchContainer.position().top
            );
            const sideBorderPadding = this.search.outerWidth(false) - this.search.width();
            const minimumWidth = measureTextWidth(this.search) + 10;
            const left = this.search.offset().left;
            const maxWidth = this.selection.width();
            const containerLeft = this.selection.offset().left;

            let searchWidth = maxWidth - (left - containerLeft) - sideBorderPadding;

            if (searchWidth < minimumWidth) {
                searchWidth = maxWidth - sideBorderPadding;
            }

            if (searchWidth <= 0) {
                searchWidth = minimumWidth;
            }

            this.search.width(Math.floor(searchWidth) - 1);
            this.selection.toggleClass('select2-first-row', isFirstRow);
        };

        prototype.updateSelection = function(data) {
            const ids = [];
            const filtered = [];
            const self = this;

            // filter out duplicates
            $(data).each(function() {
                if (indexOf(self.id(this), ids) < 0) {
                    ids.push(self.id(this));
                    filtered.push(this);
                }
            });
            data = filtered;

            this.selection.find('.select2-search-choice').remove();
            const val = this.getVal();
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
            const enableChoice = !data.locked;
            const enabledItem = $(
                '<li class=\'select2-search-choice\'>' +
                    '<div></div>' +
                    '<a href=\'#\' onclick=\'return false;\' ' +
                        'class=\'select2-search-choice-close\' tabindex=\'-1\'></a>' +
                '</li>');
            const disabledItem = $(
                '<li class=\'select2-search-choice select2-locked\'>' +
                    '<div></div>' +
                    '</li>');
            const choice = enableChoice ? enabledItem : disabledItem;
            if (data.hidden) {
                choice.addClass('hide');
            }
            const id = this.id(data);

            const formatted = this.opts.formatSelection(data, choice.find('div'), this.opts.escapeMarkup);
            if (formatted !== undefined) {
                choice.find('div').replaceWith('<div>' + formatted + '</div>');
            }
            const cssClass = this.opts.formatSelectionCssClass(data, choice.find('div'));
            if (cssClass !== undefined) {
                choice.addClass(cssClass);
            }

            if (enableChoice) {
                choice.find('.select2-search-choice-close')
                    .on('mousedown', killEvent)
                    .on('click dblclick', this.bind(function(e) {
                        if (!this.isInterfaceEnabled()) {
                            return;
                        }

                        $(e.target).closest('.select2-search-choice').fadeOut('fast', this.bind(function() {
                            this.unselect($(e.target));
                            this.selection.find('.select2-search-choice-focus')
                                .removeClass('select2-search-choice-focus');
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

        prototype.onSelect = overrideMethods.onSelect;
    }(Select2['class'].multi.prototype));
});
