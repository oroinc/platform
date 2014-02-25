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
        $stream    = $this->parser->getStream();
        $names     = [];
        $variables = null;
        while (!$stream->test(\Twig_Token::BLOCK_END_TYPE)) {
            if ($stream->test(\Twig_Token::NAME_TYPE, 'with')) {
                $stream->next();
                $variables = $this->parser->getExpressionParser()->parseExpression();

                continue;
            }

            if ($stream->test(\Twig_Token::NAME_TYPE)) {
                $names[] = $stream->getCurrent()->getValue();
                $stream->next();

                continue;
            }

            if ($stream->test(\Twig_Token::STRING_TYPE)) {
                $names[] = $stream->getCurrent()->getValue();
                $stream->next();

                continue;
            }

            if ($stream->test(\Twig_Token::OPERATOR_TYPE, '~')) {
                $stream->next();

                continue;
            }

            throw new \Twig_Error_Syntax(
                sprintf(
                    'Unexpected token "%s" of value "%s"',
                    \Twig_Token::typeToEnglish($stream->getCurrent()->getType(), $token->getLine()),
                    $token->getValue()
                ),
                $token->getLine()
            );
        }
        $name = implode($names);
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
