<?php

namespace Oro\Bundle\LayoutBundle\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;
use Twig\Node\Expression\ArrayExpression;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Token Parser for the 'block_theme' tag
 * Examples:
 *  {% block_theme layout _self %}
 *  {% block_theme layout '@Some/Layout/blocks.html.twig' %}
 *  {% block_theme layout.some_block_id '@Some/Layout/blocks.html.twig' %}
 *  {% block_theme layout with ['@Some/Layout/blocks.html.twig', '@Another/Layout/blocks.html.twig'] %}
 */
class BlockThemeTokenParser extends AbstractTokenParser
{
    /**
     * {@inheritdoc}
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $block = $this->parser->getExpressionParser()->parseExpression();

        if ($this->parser->getStream()->test(Token::NAME_TYPE, 'with')) {
            $this->parser->getStream()->next();
            $resources = $this->parser->getExpressionParser()->parseExpression();
        } else {
            $resources = new ArrayExpression(array(), $stream->getCurrent()->getLine());
            do {
                $resources->addElement($this->parser->getExpressionParser()->parseExpression());
            } while (!$stream->test(Token::BLOCK_END_TYPE));
        }

        $stream->expect(Token::BLOCK_END_TYPE);

        return new BlockThemeNode($block, $resources, $lineno, $this->getTag());
    }

    /**
     * {@inheritdoc}
     */
    public function getTag()
    {
        return 'block_theme';
    }
}
