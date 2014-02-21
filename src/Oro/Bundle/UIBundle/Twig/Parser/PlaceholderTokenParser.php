<?php
namespace Oro\Bundle\UIBundle\Twig\Parser;

use Oro\Bundle\UIBundle\Twig\Node\PlaceholderNode;

class PlaceholderTokenParser extends \Twig_TokenParser
{
    /**
     * @var array
     */
    protected $placeholders;

    protected $wrapClassName;

    /**
     * @param array  $placeholders Array with placeholders
     * @param string $wrapClassName Wrapper css class
     */
    public function __construct(array $placeholders, $wrapClassName)
    {
        $this->placeholders = $placeholders;
        $this->wrapClassName = $wrapClassName;
    }

    /**
     * {@inheritDoc}
     */
    public function parse(\Twig_Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        if ($stream->test(\Twig_Token::NAME_TYPE)) {
            $name = $stream->getCurrent()->getValue();
            $stream->next();
        } else {
            $names = [];
            while (!$stream->test(\Twig_Token::NAME_TYPE, 'with')
                && !$stream->test(\Twig_Token::BLOCK_END_TYPE)
            ) {
                if ($stream->test(\Twig_Token::STRING_TYPE)) {
                    $names[] = $stream->getCurrent()->getValue();
                }

                $stream->next();
            }
            $name = implode($names);
        }

        $variables = null;
        if ($stream->test(\Twig_Token::NAME_TYPE, 'with')) {
            $stream->next();
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        if (isset($this->placeholders[$name])) {
            return new PlaceholderNode(
                $this->placeholders[$name],
                $variables,
                $this->wrapClassName,
                $token->getLine(),
                $this->getTag()
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getTag()
    {
        return 'placeholder';
    }
}
