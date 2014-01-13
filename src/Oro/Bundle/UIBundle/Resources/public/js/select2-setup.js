/*global define*/
define(['jquery', 'jquery.select2'], function($) {
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
            id = opts.id;

        populate = function (results, container, depth) {
            var i, l, result, selectable, disabled, compound, node, label, innerContainer, formatted, subId;
            results = opts.sortResults(results, container, query);

            for (i = 0, l = results.length; i < l; i = i + 1) {
                result = results[i];

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
                    subId = container.attr('id') + '_' + depth + '_' + i;

                    innerContainer = $('<ul></ul>')
                        .addClass('select2-result-sub');
                    populate(result.children, innerContainer, depth + 1);
                    innerContainer = innerContainer
                        .wrap('<div class="accordion-body collapse" id="' + subId + '" />')
                        .parent();

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
                        .attr('data-parent', '#' + container.attr('id'))
                        .wrap('<div class="accordion-heading"/>')
                        .parent();
                }
                node.prepend(label);
                node.data('select2-data', result);
                container.append(node);
            }
        };

        container.attr('id', container.attr('id') || ('select2container_' + Date.now()));
        container.on('click.collapse.data-api', '[data-toggle=collapse]', function (e) {
            var $this = $(this),
                target = $this.attr('data-target'),
                option = $(target).data('collapse') ? 'toggle' : $this.data();
            $this[$(target).hasClass('in') ? 'addClass' : 'removeClass']('collapsed');
            $(target).collapse(option);
        });
        populate(results, container, 0);
    }

    // Overwrite methods of AbstractSelect2 class
    (function (prototype) {
        var prepareOpts = prototype.prepareOpts;
        prototype.prepareOpts = function (options) {
            if (options.collapsibleResults || options.is_group_collapsible) {
                options.populateResults = populateCollapsibleResults;
            }
            return prepareOpts.call(this, options);
        };
    } (window.Select2['class'].abstract.prototype));
});
