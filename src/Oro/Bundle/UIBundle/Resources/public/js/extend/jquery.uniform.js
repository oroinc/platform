define(function(require) {
    'use strict';

    /**
     * Add or remove extra class in Uniform container if selected value is empty
     * @param {jQuery.Element} $el
     * @param {jQuery.Element} $elParent
     */
    function markIfEmpty($el, $elParent) {
        if (!$el.length || !$elParent.length) {
            return;
        }

        $elParent.toggleClass('uniform-empty-value', $el[0].value.trim() === '');
    }

    /**
     * Add or remove extra class in Uniform container if an element is in "readonly" state
     * @param {jQuery.Element} $el
     * @param {jQuery.Element} $elParent
     */
    function markAsReadonly($el, $elParent) {
        if (!$el.length || !$elParent.length) {
            return;
        }

        $elParent.toggleClass('readonly', $el.is('[readonly]'));
    }

    const $ = require('jquery');
    const _ = require('underscore');

    require('jquery.uniform');

    const classList = ['selectClass', 'selectMultiClass'];
    const originalUniform = $.fn.uniform;

    $.fn.uniform = _.wrap($.fn.uniform, function(original, options) {
        if ($(this).is('select')) {
            const config = _.extend({}, $.uniform.defaults, options);
            const uniformParentSelectors = _.map(
                _.values(_.pick(config, classList)),
                function(selector) {
                    return '.' + selector;
                }).join(', ');

            original.call(this, config);

            return this.each(function() {
                const $el = $(this);
                const uniformContainer = $el.parent(uniformParentSelectors);

                markIfEmpty($el, uniformContainer);
                markAsReadonly($el, uniformContainer);

                $el.on('change' + config.eventNamespace, _.partial(markIfEmpty, $el, uniformContainer));
            });
        }

        return original.call(this, options);
    });

    $.fn.uniform.restore = originalUniform.restore;
    $.fn.uniform.update = originalUniform.update;
});
