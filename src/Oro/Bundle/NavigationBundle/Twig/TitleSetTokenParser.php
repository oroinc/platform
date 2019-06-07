<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Used for compiling {% oro_title_set(array) %} tag
 */
class TitleSetTokenParser extends AbstractTokenParser
{
    /**
     * Parses a token and returns a node.
     *
     * @param  Token $token A Token instance
     * @return Node  A Node instance
     */
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $expr = $this->parser->getExpressionParser()->parseArguments();
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TitleNode($expr, $lineno);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'oro_title_set';
    }
}
