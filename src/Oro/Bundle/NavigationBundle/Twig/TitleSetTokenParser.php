<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Twig\ExpressionParser\Infix\ArgumentsTrait;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

/**
 * Used for compiling {% oro_title_set(array) %} tag
 */
class TitleSetTokenParser extends AbstractTokenParser
{
    use ArgumentsTrait;

    /**
     * Parses a token and returns a node.
     *
     * @param  Token $token A Token instance
     * @return Node  A Node instance
     */
    #[\Override]
    public function parse(Token $token)
    {
        $lineno = $token->getLine();

        $expr = $this->parseNamedArguments($this->parser);
        $this->parser->getStream()->expect(Token::BLOCK_END_TYPE);

        return new TitleNode($expr, $lineno);
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    #[\Override]
    public function getTag()
    {
        return 'oro_title_set';
    }
}
