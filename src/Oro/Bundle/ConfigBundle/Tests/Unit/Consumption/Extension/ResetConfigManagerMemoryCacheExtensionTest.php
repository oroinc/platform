<?php

namespace Oro\Bundle\ConfigBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\CacheBundle\Provider\MemoryCache;
use Oro\Bundle\ConfigBundle\Consumption\Extension\ResetConfigManagerMemoryCacheExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\TestCase;

class ResetConfigManagerMemoryCacheExtensionTest extends TestCase
{
    private ResetConfigManagerMemoryCacheExtension $extension;
    private MemoryCache $memoryCache;

    protected function setUp(): void
    {
        $this->memoryCache = new MemoryCache();
        $this->extension = new ResetConfigManagerMemoryCacheExtension($this->memoryCache);
    }

    public function testOnStart(): void
    {
        $session = self::createMock(SessionInterface::class);
        $context = new Context($session);

        $this->memoryCache->set('key', 'value');
        $this->extension->onStart($context);
        self::assertNull($this->memoryCache->get('key'));
    }
}
