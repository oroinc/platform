<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\ApiBundle\Processor\UpdateListProcessor;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Component\ChainProcessor\ProcessorBagInterface;
use PHPUnit\Framework\TestCase;

class UpdateListProcessorTest extends TestCase
{
    public function testCreateContextObject(): void
    {
        $processor = new UpdateListProcessor(
            $this->createMock(ProcessorBagInterface::class),
            'update_list',
            $this->createMock(ConfigProvider::class),
            $this->createMock(MetadataProvider::class)
        );

        $context = $processor->createContext();
        self::assertInstanceOf(UpdateListContext::class, $context);
    }
}
