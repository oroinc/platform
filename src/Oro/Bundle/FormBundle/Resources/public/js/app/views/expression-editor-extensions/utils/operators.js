import __ from 'orotranslation/js/translator';
import {snippet} from '@codemirror/autocomplete';

export const OPERATORS_SNIPPET_TEMPLATES = {
    '=': '= #{1}',
    '==': '== #{1}',
    '!=': '!= #{1}',
    '>=': '>= #{1}',
    '>': '> #{1}',
    '<=': '<= #{1}',
    '<': '< #{1}',
    '*': '* #{1}',
    '/': '/ #{1}',
    '%': '% #{1}',
    '+': '+ #{1}',
    '-': '- #{1}',
    'and': 'and #{1}',
    'or': 'or #{1}',
    'in': 'in [#{1}]',
    'not in': 'not in [#{1}]',
    'matches': 'matches containsRegExp(#{1})',
    'does not match': 'not matches containsRegExp(#{1})',
    '()': '(#{1})',
    'is empty': '#{1} == 0',
    'is not empty': '#{1} != 0',
    'between': '#{1} >= #{2} and #{3} <= #{4}',
    'not between': '#{1} < #{2} and #{3} > #{4}',
    'yes': '#{1} == true',
    'no': '#{1} == false'
};

/**
 * Resolve snippet by key
 *
 * @param {string} name
 * @param {boolean} spaceBefore
 * @param {object} options
 * @returns {function}
 */
export const getResolvedSnippetByName = (name, spaceBefore = false, options = {}) => {
    let tpl = OPERATORS_SNIPPET_TEMPLATES[name.trim()];

    if (!tpl) {
        tpl = name;

        if (options.isCollection) {
            tpl += '[#{1}]';
        }

        if (options.hasChildren !== void 0) {
            tpl += options.hasChildren ? '.' : ' ';
        }

        tpl += tpl.includes('#{1}') ? '#{2}' : '#{1}';
    }

    if (spaceBefore) {
        tpl = ` ${tpl}`;
    }

    return snippet(tpl);
};

export const operatorsDetailMap = {
    '=': __('oro.form.expression_editor.operators.detail.equal'),
    '==': __('oro.form.expression_editor.operators.detail.equal'),
    '!=': __('oro.form.expression_editor.operators.detail.not_equal'),
    '>=': __('oro.form.expression_editor.operators.detail.greater_than_or_equal'),
    '>': __('oro.form.expression_editor.operators.detail.greater_than'),
    '<=': __('oro.form.expression_editor.operators.detail.less_than_or_equal'),
    '<': __('oro.form.expression_editor.operators.detail.less_than'),
    '*': __('oro.form.expression_editor.operators.detail.multiplication'),
    '/': __('oro.form.expression_editor.operators.detail.division'),
    '%': __('oro.form.expression_editor.operators.detail.remainder'),
    '+': __('oro.form.expression_editor.operators.detail.addition'),
    '-': __('oro.form.expression_editor.operators.detail.subtraction')
};
