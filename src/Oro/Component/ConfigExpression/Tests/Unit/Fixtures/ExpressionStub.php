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

    /**
     * {@inheritdoc}
     */
    public function evaluate($context, \ArrayAccess $errors = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'test';
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return '';
    }
}
