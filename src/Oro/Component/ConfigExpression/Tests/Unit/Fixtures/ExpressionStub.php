<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\Fixtures;

use Oro\Component\ConfigExpression\ExpressionInterface;

class ExpressionStub implements ExpressionInterface
{
    /** @var string */
    private $type;

    /** @var array */
    private $options;

    /** @var string|null */
    private $message;

    /**
     * @param string|null $type
     * @param array|null  $options
     * @param string|null $message
     */
    public function __construct($type = null, $options = null, $message = null)
    {
        $this->type    = $type;
        $this->options = $options;
        $this->message = $message;
    }

    #[\Override]
    public function evaluate($context, \ArrayAccess $errors = null)
    {
    }

    #[\Override]
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    #[\Override]
    public function initialize(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    #[\Override]
    public function getName()
    {
        return 'test';
    }

    #[\Override]
    public function toArray()
    {
        return [];
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return '';
    }
}
