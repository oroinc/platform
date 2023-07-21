import {HighlightStyle} from '@codemirror/language';
import {styleTags, tags as t} from '@lezer/highlight';

export const symfonyExpressionLanguageHighlighting = styleTags({
    'Operator': t.operator,
    'LiteralOperator': t.operator,
    'GroupOperator': t.operator,
    'Dot': t.punctuation,
    'BracketOpen': t.bracket,
    'BracketClose': t.bracket,
    '()': t.bracket,
    'EntityName': t.variableName,
    'PropertyName': t.propertyName,
    'String': t.string,
    'Number': t.integer,
    'FunctionName': t.literal
});

export const symfonyExpressionLanguageHighlightStyle = HighlightStyle.define([
    {
        'tag': t.name,
        'class': 'cm-tag-name'
    },
    {
        'tag': t.variableName,
        'class': 'cm-tag-name'
    },
    {
        'tag': t.propertyName,
        'class': 'cm-tag-property-name'
    },
    {
        'tag': t.literal,
        'class': 'cm-tag-literal'
    },
    {
        'tag': t.string,
        'class': 'cm-tag-string'
    },
    {
        'tag': t.integer,
        'class': 'cm-tag-number'
    },
    {
        'tag': t.unit,
        'class': 'cm-tag-literal'
    },
    {
        'tag': t.null,
        'class': 'cm-tag-literal'
    },
    {
        'tag': t.keyword,
        'class': 'cm-tag-punctuation'
    },
    {
        'tag': t.punctuation,
        'class': 'cm-tag-punctuation'
    },
    {
        'tag': t.derefOperator,
        'class': 'cm-tag-punctuation'
    },
    {
        'tag': t.bracket,
        'class': 'cm-tag-punctuation'
    },
    {
        'tag': t.operator,
        'class': 'cm-tag-operator'
    }
],
{
    all: {
        'class': 'cm-tag-common'
    }
});
