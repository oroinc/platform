import OriginalExpressionLanguage from 'oroexpressionlanguage/js/library/expression-language';
import Lexer from './lexer';
import Parser from './parser';

class ExpressionLanguage extends OriginalExpressionLanguage {
    getLexer() {
        if (null === this.lexer) {
            this.lexer = new Lexer();
        }

        return this.lexer;
    }

    getParser() {
        if (null === this.parser) {
            this.parser = new Parser(this.functions);
        }

        return this.parser;
    }
}

export default ExpressionLanguage;
