<?php
namespace Oro\Bundle\UIBundle\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Node\PlaceholderNode;

class PlaceholderTokenParser extends \Twig_TokenParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $stream = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();

        if ($stream->test(\Twig_Token::NAME_TYPE)) {
            $currentToken = $stream->getCurrent();
            $currentValue = $currentToken->getValue();
            $currentLine = $currentToken->getLine();

            // Creates expression: placeholder_name|default('placeholder_name')
            // To parse either variable value or name
            $name = new \Twig_Node_Expression_Filter_Default(
                new \Twig_Node_Expression_Name($currentValue, $currentLine),
                new \Twig_Node_Expression_Constant('default', $currentLine),
                new \Twig_Node(
                    array(
                        new \Twig_Node_Expression_Constant(
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

        if ($stream->nextIf(\Twig_Token::NAME_TYPE, 'with')) {
            $variables = $expressionParser->parseExpression();
        } else {
            $variables = new \Twig_Node_Expression_Constant(array(), $token->getLine());
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new PlaceholderNode(
            $name,
            $variables,
            $token->getLine(),
            $this->getTag()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return 'placeholder';
    }
}
