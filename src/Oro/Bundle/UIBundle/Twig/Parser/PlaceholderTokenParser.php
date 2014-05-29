<?php

namespace Oro\Bundle\UIBundle\Twig\Parser;

class PlaceholderTokenParser extends \Twig_TokenParser
{
    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $stream           = $this->parser->getStream();
        $expressionParser = $this->parser->getExpressionParser();

        if ($stream->test(\Twig_Token::NAME_TYPE)) {
            $currentToken = $stream->getCurrent();
            $currentValue = $currentToken->getValue();
            $currentLine  = $currentToken->getLine();

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

        $attributes = array();

        $outputVariableName = null;
        if ($stream->nextIf(\Twig_Token::NAME_TYPE, 'set')) {
            $outputVariableName = $stream->expect(\Twig_Token::NAME_TYPE)->getValue();
            // 'output' => 'raw'
            $attributes[] = new \Twig_Node_Expression_Constant('output', 0);
            $attributes[] = new \Twig_Node_Expression_Constant('raw', 0);
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        // build expression to call 'placeholder' function
        $expr = new \Twig_Node_Expression_Function(
            'placeholder',
            new \Twig_Node(
                array(
                    'name'       => $name,
                    'variables'  => $variables,
                    'attributes' => new \Twig_Node_Expression_Array($attributes, $token->getLine())
                )
            ),
            $token->getLine()
        );

        if (empty($outputVariableName)) {
            $result = new \Twig_Node_Print($expr, $token->getLine(), $this->getTag());
        } else {
            $result = new \Twig_Node_Set(
                false,
                new \Twig_Node(array(new \Twig_Node_Expression_AssignName($outputVariableName, 0))),
                new \Twig_Node(array($expr)),
                $token->getLine(),
                $this->getTag()
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return 'placeholder';
    }
}
