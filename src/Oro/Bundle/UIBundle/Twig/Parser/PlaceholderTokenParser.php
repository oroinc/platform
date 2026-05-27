<?php

namespace Oro\Bundle\UIBundle\Twig\Parser;

use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\Variable\ContextVariable;
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
    #[\Override]
    public function parse(Token $token)
    {
        $stream = $this->parser->getStream();

        if ($stream->test(Token::NAME_TYPE)) {
            $currentToken = $stream->getCurrent();
            $currentValue = $currentToken->getValue();
            $currentLine  = $currentToken->getLine();

            // Creates expression: placeholder_name|default('placeholder_name')
            // To parse either variable value or name
            $contextVar = new ContextVariable($currentValue, $currentLine);
            $contextVar->setAttribute('ignore_strict_check', true);

            $name = new DefaultFilter(
                $contextVar,
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
            $name = $this->parser->parseExpression();
        }

        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            $variables = $this->parser->parseExpression();
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

        return new PrintNode($expr, $token->getLine());
    }

    #[\Override]
    public function getTag()
    {
        return 'placeholder';
    }
}
