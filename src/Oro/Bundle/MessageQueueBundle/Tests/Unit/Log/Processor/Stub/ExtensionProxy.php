<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Log\Processor\Stub;

use ProxyManager\Proxy\ValueHolderInterface;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

class ExtensionProxy extends AbstractExtension implements ValueHolderInterface
{
    /** @var ExtensionInterface */
    private $extension;

    /**
     * @param ExtensionInterface $extension
     */
    public function __construct(ExtensionInterface $extension)
    {
        $this->extension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function getWrappedValueHolderValue()
    {
        return $this->extension;
    }
}
