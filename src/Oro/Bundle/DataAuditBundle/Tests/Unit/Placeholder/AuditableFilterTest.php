<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DataAuditBundle\Placeholder\AuditableFilter;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditableFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $configProvider;

    /** @var AuditableFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->configProvider = $this->createMock(ConfigProvider::class);

        $this->filter = new AuditableFilter($this->configProvider);
    }

    public function testIsEntityAuditableWithForceShow()
    {
        $this->configProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertTrue(
            $this->filter->isEntityAuditable(new LoggableClass(), true)
        );
    }

    public function testIsEntityAuditableWithForceShowAndNotObject()
    {
        $this->configProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertTrue(
            $this->filter->isEntityAuditable(null, true)
        );
    }

    public function testIsEntityAuditableWithNull()
    {
        $this->configProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isEntityAuditable(null, false)
        );
    }

    public function testIsEntityAuditableWithNotObject()
    {
        $this->configProvider->expects($this->never())
            ->method('hasConfig');

        $this->assertFalse(
            $this->filter->isEntityAuditable('test', false)
        );
    }

    public function testIsEntityAuditableWithNotConfigurableEntity()
    {
        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(LoggableClass::class)
            ->willReturn(false);

        $this->assertFalse($this->filter->isEntityAuditable(new LoggableClass(), false));
    }

    public function testIsEntityAuditable()
    {
        $config = new Config(new EntityConfigId('dataaudit', LoggableClass::class));
        $config->set('auditable', true);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(LoggableClass::class)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(LoggableClass::class)
            ->willReturn($config);

        $this->assertTrue(
            $this->filter->isEntityAuditable(new LoggableClass(), false)
        );
    }
}
