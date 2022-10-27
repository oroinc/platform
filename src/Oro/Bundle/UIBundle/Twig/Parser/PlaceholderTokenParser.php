<?php

namespace Oro\Bundle\UIBundle\Twig\Parser;

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Provides a Twig tag for using placeholders in Twig templates:
 *   - placeholder
 *
 * Usage example:
 *     {% placeholder page_header_stats_before with {entity: entity} %}
 */
class PlaceholderTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(Token $token)
    {
        $stream           = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();

        if ($stream->test(Token::NAME_TYPE)) {
            $currentToken = $stream->getCurrent();
            $currentValue = $currentToken->getValue();
            $currentLine  = $currentToken->getLine();

            // Creates expression: placeholder_name|default('placeholder_name')
            // To parse either variable value or name
            $name = new DefaultFilter(
                new NameExpression($currentValue, $currentLine),
                new ConstantExpression('default', $currentLine),
                new Node(
                    array(
                        new ConstantExpression(
                            $currentValue,
                            $currentLine
                        )
                    ),
                    array(),
                    $currentLine
                ),
                $currentLine
            );

            $stream->next();
        } else {
            $name = $expressionParser->parseExpression();
        }

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $expressionParser->parseExpression();
        } else {
            $variables = new ConstantExpression(array(), $token->getLine());
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        // build expression to call 'placeholder' function
        $expr = new FunctionExpression(
            'placeholder',
            new Node(
                array(
                    'name'       => $name,
                    'variables'  => $variables
                )
            ),
            $token->getLine()
        );

        return new PrintNode($expr, $token->getLine(), $this->getTag());
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return 'placeholder';
    }
}
