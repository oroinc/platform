<?php

namespace Oro\Component\Action\Tests\Unit\Action\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\ConfigExpression\ExpressionInterface;

class ArrayCondition extends ArrayCollection implements ExpressionInterface
{
    /** @var string */
    private $message;

    #[\Override]
    public function evaluate($context, ?\ArrayAccess $errors = null)
    {
        $result = $this->isConditionAllowed($context);

        return $result;
    }

    public function getMessage()
    {
        return $this->message;
    }

    #[\Override]
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @param array $options
     * @return ExpressionInterface
     */
    #[\Override]
    public function initialize(array $options)
    {
        foreach ($options as $key => $value) {
            $this->set($key, $value);
        }
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
        return 'array';
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return '';
    }

    /**
     * Checks if context meets the condition requirements.
     *
     * @param mixed $context
     *
     * @return boolean
     */
    public function isConditionAllowed($context)
    {
        $isAllowed = $this->get('allowed');

        return $isAllowed ? true : false;
    }
}
