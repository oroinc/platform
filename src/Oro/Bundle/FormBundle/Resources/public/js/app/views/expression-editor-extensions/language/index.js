import {LRLanguage, LanguageSupport, syntaxTree} from '@codemirror/language';
import parser from './syntax.grammar';
import {symfonyExpressionLanguageHighlighting} from './highlight';
import {operatorsDetailMap, getResolvedSnippetByName} from '../utils/operators';

const resolveAutocompleteData = ({items}) => {
    return Object.entries(items).sort().map(([item, {hasChildren}]) => {
        return {
            label: item,
            apply: getResolvedSnippetByName(item + (hasChildren ? '.' : ' ')),
            detail: operatorsDetailMap[item]
        };
    });
};

const symfonyExpressionAutocomplete = (util, context) => {
    const nodeBefore = syntaxTree(context.state).resolveInner(context.pos, -1);

    const autocompleteData = resolveAutocompleteData(
        util.getAutocompleteData(context.state.doc.lineAt(context.pos).text, context.pos)
    );

    const {text: space} = context.matchBefore(/\s*/);

    const from = nodeBefore.name === 'Dot' || space === ' ' ? context.pos : nodeBefore.from;

    if (autocompleteData.length) {
        return {
            from,
            options: autocompleteData
        };
    }
};

const parserWithMetadata = parser.configure({
    props: [
        symfonyExpressionLanguageHighlighting
    ]
});

export const symfonyExpressionLanguage = LRLanguage.define({
    name: 'symfony-expression-lang',
    parser: parserWithMetadata
});

export const symfonyExpressionCompletion = util => {
    return symfonyExpressionLanguage.data.of({
        autocomplete: symfonyExpressionAutocomplete.bind(null, util)
    });
};

export function symfonyExpression(util) {
    return new LanguageSupport(symfonyExpressionLanguage, [symfonyExpressionCompletion(util)]);
}
