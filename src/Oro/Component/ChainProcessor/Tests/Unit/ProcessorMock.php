<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class ProcessorMock implements ProcessorInterface
{
    /** @var string */
    protected $processorId;

    /** @var callable|null */
    protected $callback;

    /**
     * @param string|null   $processorId
     * @param callable|null $callback
     */
    public function __construct($processorId = null, $callback = null)
    {
        $this->processorId = $processorId;
        $this->callback    = $callback;
    }

    /**
     * @return string
     */
    public function getProcessorId()
    {
        return $this->processorId;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        if (null !== $this->callback) {
            call_user_func($this->callback, $context);
        }
    }
}
