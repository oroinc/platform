import $ from 'jquery';
import _ from 'underscore';
import __ from 'orotranslation/js/translator';
import emailUtil from 'oroemail/js/util/email';
import patterns from 'oroui/js/tools/patterns';
import 'jquery.validate';

const emailRegExp = patterns.email;
const defaultParam = {
    message: 'This value is not a valid email address.'
};

export default [
    'Oro\\Bundle\\EmailBundle\\Validator\\Constraints\\EmailAddress',
    function(value, element) {
        const $el = $(element);

        if ($el.parent().is(':hidden')) {
            return true;
        }

        if (_.isEmpty(value)) {
            return false;
        }

        // @TODO add support of MX check action
        // original email validator is too slow for some values
        // return $.validator.methods.email.apply(this, arguments);

        let values = null;
        if ($el.data('select2')) {
            const data = $el.inputWidget('data');
            const arrayData = Array.isArray(data) ? data : [data];
            values = _.map(arrayData, _.partial(_.result, _, 'text'));
        } else {
            values = [value];
        }

        return this.optional(element) || _.every(values, function(val) {
            return emailRegExp.test(emailUtil.extractPureEmailAddress(val));
        });
    },
    function(param, element) {
        const value = this.elementValue(element);
        const placeholders = {};
        param = _.extend({}, defaultParam, param);
        placeholders.value = value;
        return __(param.message, placeholders);
    }
];
