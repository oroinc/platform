<?php

namespace Oro\Bundle\LayoutBundle\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\LayoutThemeNode;

/**
 * Token Parser for the 'layout_theme' tag
 * Examples:
 *  {% layout_theme layout _self %}
 *  {% layout_theme layout 'SomeBundle:Layout:blocks.html.twig' %}
 *  {% layout_theme layout.some_block_id 'SomeBundle:Layout:blocks.html.twig' %}
 *  {% layout_theme layout with ['SomeBundle:Layout:blocks.html.twig', 'AnotherBundle:Layout:blocks.html.twig'] %}
 */
class LayoutThemeTokenParser extends \Twig_TokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(\Twig_Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $block = $this->parser->getExpressionParser()->parseExpression();

        if ($this->parser->getStream()->test(\Twig_Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();
            $resources = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $resources = new \Twig_Node_Expression_Array(array(), $stream->getCurrent()->getLine());
            do {
                $resources->addElement($this->parser->getExpressionParser()->parseExpression());
            } while (!$stream->test(\Twig_Token::BLOCK_END_TYPE));
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new LayoutThemeNode($block, $resources, $lineno, $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'layout_theme';
    }
}
