import ExpressionSyntaxError from 'oroexpressionlanguage/js/library/expression-syntax-error';

describe('oroexpressionlanguage/js/library/expression-syntax-error', () => {
    it('inherit Error prototype', () => {
        const error = new ExpressionSyntaxError('missing comma');
        expect(error).toEqual(jasmine.any(Error));
    });

    it('default cursor position value', () => {
        const error = new ExpressionSyntaxError('missing comma');
        expect(error.message).toBe('missing comma around position 0.');
    });

    it('with cursor position value', () => {
        const error = new ExpressionSyntaxError('missing comma', 5);
        expect(error.message).toBe('missing comma around position 5.');
    });

    it('with quoted expression', () => {
        const error = new ExpressionSyntaxError('missing comma', 5, 'foo(a b)');
        expect(error.message).toBe('missing comma around position 5 for expression `foo(a b)`.');
    });
});
