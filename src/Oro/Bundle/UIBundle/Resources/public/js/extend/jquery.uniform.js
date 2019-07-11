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

    var $ = require('jquery');
    var _ = require('underscore');

    require('jquery.uniform');

    var classList = ['selectClass', 'selectMultiClass'];
    var originalUniform = $.fn.uniform;

    $.fn.uniform = _.wrap($.fn.uniform, function(original, options) {
        if ($(this).is('select')) {
            var config = _.extend({}, $.uniform.defaults, options);
            var uniformParentSelectors = _.map(
                _.values(_.pick(config, classList)),
                function(selector) {
                    return '.' + selector;
                }).join(', ');

            original.call(this, config);

            return this.each(function() {
                var $el = $(this);
                var uniformContainer = $el.parent(uniformParentSelectors);

                markIfEmpty($el, uniformContainer);

                $el.on('change' + config.eventNamespace, _.partial(markIfEmpty, $el, uniformContainer));
            });
        }

        return original.call(this, options);
    });

    $.fn.uniform.restore = originalUniform.restore;
    $.fn.uniform.update = originalUniform.update;
});
