<?php

namespace Oro\Bundle\FilterBundle\Expression\Exception;

use Oro\Bundle\DataGridBundle\Exception\UserInputErrorExceptionInterface;

/**
 * Expression denied exception
 */
class ExpressionDenied extends SyntaxException implements UserInputErrorExceptionInterface
{
    private string $template = 'Variable of type “%s” cannot be used with a constant!';

    private string $variableLabel;

    /**
     * @param string $variableLabel
     */
    public function __construct($variableLabel)
    {
        $this->variableLabel = $variableLabel;
        $message             = sprintf($this->template, $variableLabel);
        parent::__construct($message);
    }

    #[\Override]
    public function getMessageTemplate(): string
    {
        return $this->template;
    }

    #[\Override]
    public function getMessageParams(): array
    {
        return ['%s' => $this->variableLabel];
    }
}
