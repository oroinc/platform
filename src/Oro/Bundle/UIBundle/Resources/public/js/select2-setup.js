/*jshint browser:true*/
/*jslint browser:true*/
/*global define*/
define(['jquery', 'jquery.select2'], function ($) {
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
            var i, l, result, selectable, disabled, compound, node, label, innerContainer, formatted, subId, parent, resultId;
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

    // Overwrite methods of AbstractSelect2 class
    (function (prototype) {
        var prepareOpts = prototype.prepareOpts;
        prototype.prepareOpts = function (options) {
            if (options.collapsibleResults) {
                options.populateResults = populateCollapsibleResults;
                var matcher = options.matcher || $.fn.select2.defaults.matcher;
                options.matcher = function (term, text, option) {
                    return !option.children && matcher.apply(this, arguments);
                };
            }
            return prepareOpts.call(this, options);
        };
    }(window.Select2['class'].abstract.prototype));
});
