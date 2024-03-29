import TranslatorProvider from '../../query-type-converter/translator-provider';
import {filterFromExpression, conditionFromExpression} from '../../query-type-converter/from-expression';
import {filterToExpression, conditionToExpression} from '../../query-type-converter/to-expression';

Object.entries({
    filterFromExpression,
    conditionFromExpression,
    filterToExpression,
    conditionToExpression
}).forEach(([groupName, translators]) => {
    const [BaseTranslator, ...restTranslators] = translators;
    const Provider = TranslatorProvider.createProvider(groupName, BaseTranslator);
    restTranslators.forEach(Translator => {
        Provider.registerTranslator(Translator.TYPE || Translator.name, Translator);
    });
});
