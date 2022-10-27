define(function(require) {
    'use strict';

    const $ = require('jquery');
    const _ = require('underscore');
    const Select2 = require('jquery.select2');
    const tools = require('oroui/js/tools');
    const __ = require('orotranslation/js/translator');
    const KEY_CODES = require('oroui/js/tools/keyboard-key-codes').default;
    const singleChoiceTpl = require('tpl-loader!oroui/templates/select2/single-choice.html');
    const multipleChoiceTpl = require('tpl-loader!oroui/templates/select2/multiple-choice.html');

    require('oroui/js/select2-l10n');
    require('jquery-ui/position');

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

    /**
     *  Add aria attributes and roles to some additional elements at select2 dropdown
     *
     * @param {jQuery.Element} $dropdown
     */
    function makeExtraElementsAccessible($dropdown) {
        $dropdown.find('.select2-no-results, .select2-searching').attr({
            'role': 'alert',
            'aria-live': 'assertive'
        });

        $dropdown.find('select2-more-results').attr({
            'role': 'option',
            'aria-disabled': true
        });
    }

    function afterProcessResults(data) {
        const results = _.result(data, 'results') || [];
        if (results.length > 0 && this.opts.dontSelectFirstOptionOnOpen) {
            this.results
                .find('.select2-highlighted').removeClass('select2-highlighted').attr('aria-selected', false);
            this.dropdown.add(this.search).one('keydown', () => delete this.opts.dontSelectFirstOptionOnOpen);
        }
    }

    /**
     * @param {jQuery.Element} $realSelect
     * @param {jQuery.Element} $select2Element
     * @returns {boolean}
     */
    function toAssignAriaAttributesForSelect($realSelect, $select2Element) {
        $realSelect.on('validate-element', event => {
            if (event.errorClass === void 0 || event.invalid === void 0) {
                return;
            }

            const ariaRequired = $(event.target).attr('aria-required');

            if (ariaRequired) {
                $select2Element.attr('aria-required', ariaRequired);
            }

            $select2Element
                .attr({
                    'aria-invalid': event.invalid,
                    'aria-describedby': $(event.target).attr('aria-describedby')
                })
                .toggleClass(event.errorClass, event.invalid);
        });

        if ($realSelect.is('[required], [data-rule-required], .required')) {
            $select2Element.attr('aria-required', true);
        }

        if ($realSelect.attr('aria-label')) {
            $select2Element.attr('aria-label', $realSelect.attr('aria-label'));
        } else if ($realSelect.attr('aria-labelledby')) {
            $select2Element.attr('aria-labelledby', $realSelect.attr('aria-labelledby'));
        } else {
            const $relatedLabel = $('label[for="' + $select2Element.attr('id') + '"]');

            // Should add aria-label to the original element because after initialization the corresponding label
            // will have another form-related element to trick WAVE checker.
            if ($relatedLabel.length) {
                $realSelect.attr('aria-label', $relatedLabel[0].childNodes[0].textContent);

                return true;
            }
        }

        return false;
    }

    function toUnAssignAriaAttributesForSelect() {
        this.opts.element
            .removeAttr('aria-hidden')
            .off('validate-element');

        if (this._ariaLabelAdded) {
            this.opts.element.removeAttr('aria-label');
        }
    }

    function preventOverlapSelectResults() {
        const dropMask = document.getElementById('select2-drop-mask');
        const container = this.container.get(0);
        const dropdown = this.dropdown.get(0);

        const {x: containerX, y: containerY, width: containerWidth} = container.getBoundingClientRect();
        const {x: dropdownX, y: dropdownY, width: dropdownWidth} = dropdown.getBoundingClientRect();

        dropMask.style.display = 'none';
        const foundContainer = document.elementsFromPoint(containerX + containerWidth / 2, containerY);
        const foundDropdown = document.elementsFromPoint(dropdownX + dropdownWidth / 2, dropdownY);

        const foundOverlapFixed = [...foundContainer, ...foundDropdown].find(
            element => getComputedStyle(element, null).getPropertyValue('position') === 'fixed'
        );

        if (foundOverlapFixed) {
            this.close();
            return;
        }
        dropMask.style.display = '';
    }

    function killEvent(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    const overrideMethods = {
        moveHighlight: function(original, ...rest) {
            if (this.highlight() === -1) {
                this.highlight(0);
            } else {
                original.apply(this, rest);
            }
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
            // Detaches the selected option and attaches it to the end of collection. Ensures correct order of
            // the submitted options.
            const option = $(this.select).children('[value="' + $.escapeSelector(data.id) + '"]');
            option.detach();
            $(this.select).append(option).change();

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
            this.container.toggleClass('select2-container-with-searchbox', showSearchInput);
            this.search.attr('aria-hidden', !showSearchInput);
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
            options.isRTL = _.isRTL();

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

            preparedOptions.populateResults = _.wrap(preparedOptions.populateResults,
                function(original, container, results, query) {
                    original.call(this, container, results, query);

                    const $results = $(container).find('.select2-result');

                    if ($results.length) {
                        $results.each((index, el) => {
                            const $el = $(el);

                            $el.attr({
                                id: _.uniqueId('select2-result-'),
                                role: 'option'
                            });

                            if ($(this).hasClass('select2-disabled')) {
                                $el.attr.attr('aria-disabled', true);
                            }
                        });
                    }
                });

            return preparedOptions;
        };

        prototype.positionDropdown = function() {
            const $container = this.container;
            positionDropdown.call(this);

            if (this.dropdownFixedMode) {
                const dropdownCss = {
                    top: this.container[0].getBoundingClientRect().top,
                    position: 'fixed'
                };

                if (this.dropdown.hasClass('select2-drop-above')) {
                    dropdownCss.top -= this.dropdown.height();
                } else {
                    dropdownCss.top += this.container.outerHeight(false);
                }

                // Fix bug on iOS with incorrect value of getBoundingClientRect
                // when keyboard is appeared and window inner height is smaller than viewport height
                if (tools.isIOS()) {
                    const fakeDiv = document.createElement('div');
                    Object.assign(fakeDiv.style, {
                        width: '1px',
                        height: '100%',
                        position: 'fixed',
                        top: 0,
                        left: 0
                    });
                    document.body.append(fakeDiv);
                    dropdownCss.top += fakeDiv.offsetHeight - window.innerHeight;
                    fakeDiv.remove();
                }

                this.dropdown.css(dropdownCss);
            } else if (this.dropdown.css('position') === 'fixed') {
                this.dropdown.css('position', '');
            }

            const dialogIsBelow = $container.hasClass('select2-dropdown-open') &&
                !$container.hasClass('select2-drop-above');
            if ($container.parent().hasClass(select2DropBelowClassName) !== dialogIsBelow) {
                $container.parent().toggleClass(select2DropBelowClassName, dialogIsBelow);
                this.opts.element.trigger('select2:dialogReposition', dialogIsBelow ? 'below' : 'top');
            }

            if (this.opts.isRTL) {
                const $dropdown = this.dropdown;
                const containerOffset = this.container.offset();
                const bodyWidth = $('body').outerWidth(true);
                const containerOffsetRight = containerOffset.left + this.container.outerWidth(false);
                let dropdownRight = bodyWidth - containerOffsetRight;
                let enoughRoomOnLeft = bodyWidth - containerOffsetRight > 0;
                let $resultsEl;

                if (this.opts.dropdownAutoWidth) {
                    $resultsEl = $('.select2-results', $dropdown);
                    enoughRoomOnLeft = $resultsEl.offset().left >= 0;
                }

                if (!enoughRoomOnLeft) {
                    dropdownRight = dropdownRight - Math.abs($resultsEl.offset().left) - $.position.scrollbarWidth();
                }

                $dropdown.css({
                    right: dropdownRight,
                    left: ''
                });
            }
        };

        prototype.open = function() {
            // Add unique name for select2 search for disabling auto-fill, auto-complete functions.
            this.search.attr('name', select2SearchName);
            this.selection.attr('aria-expanded', true);
            this.results.attr('aria-expanded', true);
            this.results.attr('aria-hidden', false);

            if (this.opts.closeOnOverlap && !this.dropdownFixedMode) {
                $(window).on('scroll.select2Overlaps', preventOverlapSelectResults.bind(this));
            }

            this.container.trigger('clearMenus'); // hides all opened dropdown menus

            return open.call(this);
        };

        /**
         * The method is fully overridden due to adjust select2 mask by CSS only.
         * Performs the opening of the dropdown.
         * @override
         */
        prototype.opening = function() {
            this.container.addClass('select2-dropdown-open select2-container-active');
            this.clearDropdownAlignmentPreference();

            if (this.dropdown[0] !== this.body().children().last()[0]) {
                this.dropdown.detach().appendTo(this.body());
            }

            // create the dropdown mask if doesnt already exist
            let mask = $('#select2-drop-mask');

            if (mask.length === 0) {
                mask = $(document.createElement('div'));
                mask
                    .attr({
                        'id': 'select2-drop-mask',
                        'class': 'select2-drop-mask'
                    })
                    .hide()
                    .appendTo(this.body())
                    .on('mousedown touchstart click', function(e) {
                        const dropdown = $('#select2-drop');

                        if (dropdown.length > 0) {
                            const select2 = dropdown.data('select2');
                            if (select2.opts.selectOnBlur) {
                                select2.selectHighlighted({noFocus: true});
                            }
                            select2.close();
                            e.preventDefault();
                            e.stopPropagation();
                        }
                    });
            }

            // ensure the mask is always right before the dropdown
            if (this.dropdown.prev()[0] !== mask[0]) {
                this.dropdown.before(mask);
            }

            // move the global id to the correct dropdown
            $('#select2-drop').removeAttr('id');
            this.dropdown.attr('id', 'select2-drop');
            // show the elements
            mask.show();
            this.dropdown.show();
            this.positionDropdown();
            this.dropdown.addClass('select2-drop-active');

            // attach listeners to events that can change the position of the container and thus require
            // the position of the dropdown to be updated as well so it does not come unglued from the container
            const cid = this.containerId;

            this.container.parents().add(window).each((i, el) => {
                $(el).on(`scroll.${cid} resize.${cid} orientationchange.${cid}`, e => {
                    this.positionDropdown();
                });
            });
        };

        prototype.close = function() {
            close.call(this);
            this.container.parent().removeClass(select2DropBelowClassName);
            // Remove previously auto generated name
            this.search.removeAttr('name');
            this.selection.attr('aria-expanded', false);
            this.results.attr('aria-expanded', false);
            this.results.attr('aria-hidden', true);
            this._activedescendantElements.removeAttr('aria-activedescendant');

            if (this.opts.closeOnOverlap && !this.dropdownFixedMode) {
                $(window).off('scroll.select2Overlaps');
            }
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

            this.opts.element
                .attr('aria-hidden', true)
                .trigger($.Event('select2-init'));

            this.container
                .add(this.dropdown)
                .addClass(`select2-direction-${_.isRTL() ? 'end' : 'start'}`);
        };


        prototype.destroy = function() {
            toUnAssignAriaAttributesForSelect.call(this);

            if (this.propertyObserver) {
                this.propertyObserver.disconnect();
                delete this.propertyObserver;
                this.propertyObserver = null;
            }

            this.breadcrumbs.off('.select2');
            // Remove previously auto generated name
            this.search.removeAttr('name');
            delete this._activedescendantElements;
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

        prototype.highlightUnderEvent = _.wrap(prototype.highlightUnderEvent, function(original, event) {
            original.call(this, event);

            // if we are over an unselectable item remove al highlights
            if (!$(event.target).closest('.select2-result-selectable').length) {
                this.results.find('.select2-highlighted').attr('aria-selected', false);
            }
        });

        prototype.enable = _.wrap(prototype.enable, function(original, enabled) {
            const returnValue = original.call(this, enabled);
            const $choiceClose = this.container.find('.select2-search-choice-close');

            if (this._enabled) {
                this.selection.removeAttr('aria-disabled');
                $choiceClose.removeAttr('aria-disabled');
            } else {
                this.selection.attr('aria-disabled', true);
                $choiceClose.attr('aria-disabled', true);
            }

            return returnValue;
        });

        prototype.highlight = _.wrap(prototype.highlight, function(original, index) {
            const choices = this.findHighlightableChoices();

            if (index === void 0) {
                return choices.get().indexOf(choices.filter('.select2-highlighted')[0]);
            }

            original.call(this, index);

            this._activedescendantElements.attr('aria-activedescendant', $(choices[index]).attr('id'));
        });
    }(Select2['class'].abstract.prototype));

    // Override methods of SingleSelect2 class
    (function(prototype) {
        const clear = prototype.clear;
        const isPlaceholderOptionSelected = prototype.isPlaceholderOptionSelected;
        const toggleAriaSelected = function() {
            this.findHighlightableChoices().each((index, el) => {
                $(el).attr('aria-selected', this.id($(el).data('select2-data')) === this.opts.element.val());
            });
        };

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

            toggleAriaSelected.call(this);
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

        prototype.updateResults = _.wrap(prototype.updateResults, function(original, initial) {
            original.call(this, initial);
            if (initial === true && this.opts.element.val()) {
                this.pagePath = this.opts.element.val();
            }
            this.updateBreadcrumbs();
            this.positionDropdown();
            makeExtraElementsAccessible(this.results);
        });

        prototype.clear = function() {
            this.pagePath = '';
            clear.call(this);
        };

        prototype.createContainer = function() {
            return $(singleChoiceTpl());
        };

        prototype.postprocessResults = _.wrap(prototype.postprocessResults, function(original, data, ...rest) {
            original.call(this, data, ...rest);
            afterProcessResults.call(this, data);
            toggleAriaSelected.call(this);
        });

        prototype.moveHighlight = _.wrap(prototype.moveHighlight, overrideMethods.moveHighlight);

        prototype.initContainer = _.wrap(prototype.initContainer, function(original, ...rest) {
            original.apply(this, rest);

            this._ariaLabelAdded = toAssignAriaAttributesForSelect(this.opts.element, this.focusser);

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
            // Open dropdown by SPACE key
            // Solution resolved in a way as it does by ENTER key https://github.com/select2/select2/blob/3.5.4/select2.js#L2354-L2367
            this.focusser.on('keydown', e => {
                if (this.opts.openOnEnter === false && e.keyCode === KEY_CODES.SPACE) {
                    killEvent(e);
                    return;
                }
                if (e.keyCode === KEY_CODES.SPACE && this.opts.openOnEnter) {
                    if (e.altKey || e.ctrlKey || e.shiftKey || e.metaKey) {
                        return;
                    }
                    this.open();
                    killEvent(e);
                }
            });

            this.search.off('blur');
            this.search.on('blur', this.bind(function(e) {
                // a workaround for chrome to keep the search field focussed when the scroll bar is used to scroll the dropdown.
                // without this the search field loses focus which is annoying
                if ((e.relatedTarget && e.relatedTarget.nodeName !== 'INPUT') &&
                    document.activeElement === this.body().get(0)
                ) {
                    window.setTimeout(this.bind(function() {
                        this.search.focus();
                    }), 0);
                }
            }));

            this._activedescendantElements = this.search;

            if (this.focusser) {
                this._activedescendantElements.add(this.focusser);
            }
        });
        prototype.tokenize = _.wrap(prototype.tokenize, overrideMethods.tokenize);
        prototype.showSearch = _.wrap(prototype.showSearch, overrideMethods.showSearch);
    }(Select2['class'].single.prototype));

    // Override methods of MultiSelect2 class
    // Fix is valid for version 3.4.1
    (function(prototype) {
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
            const enabledItem = $('<li class="select2-search-choice"></li>').html(`
                <div></div>
                <a href='#' onclick='return false;' class='select2-search-choice-close' tabindex='-1'></a>
            `);
            const disabledItem = $('<li class="select2-search-choice select2-locked"><div></div></li>');
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

        prototype.postprocessResults = _.wrap(prototype.postprocessResults, function(original, data, ...rest) {
            original.call(this, data, ...rest);
            afterProcessResults.call(this, data);

            const $selectedChoices = this.results.find('.select2-result').add(
                this.results.find('.select2-result-with-children')
            );

            $selectedChoices
                .each((index, el) => $(el).attr('aria-selected', $(el).hasClass('select2-selected')));

            this.selection.find('.select2-search-choice').attr('role', 'presentation');
            this.selection.find('.select2-search-choice-close').each((index, el) => {
                $(el).attr({
                    'role': 'button',
                    'aria-label': `${__('oro.ui.select2.remove_selected_item', {name: $(el).prev().text()})}`
                });
            });
        });

        prototype.moveHighlight = _.wrap(prototype.moveHighlight, overrideMethods.moveHighlight);

        prototype.onSelect = overrideMethods.onSelect;

        prototype.createContainer = function() {
            return $(multipleChoiceTpl());
        };

        prototype.initContainer = _.wrap(prototype.initContainer, function(original, ...rest) {
            original.apply(this, rest);
            this._ariaLabelAdded = toAssignAriaAttributesForSelect(this.opts.element, this.search);
            this._activedescendantElements = this.search;

            if (this.focusser) {
                this._activedescendantElements.add(this.focusser);
            }
        });

        prototype.updateResults = _.wrap(prototype.updateResults, function(original, initial) {
            original.call(this, initial);
            makeExtraElementsAccessible(this.results);
        });
    }(Select2['class'].multi.prototype));
});
