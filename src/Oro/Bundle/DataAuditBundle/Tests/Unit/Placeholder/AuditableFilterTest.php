<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Placeholder;

use Oro\Bundle\DataAuditBundle\Placeholder\AuditableFilter;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Fixture\LoggableClass;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class AuditableFilterTest extends \PHPUnit\Framework\TestCase
{
    const TEST_ENTITY_REFERENCE = LoggableClass::class;

    /**
     * @var AuditableFilter
     */
    protected $filter;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $configProvider;

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
            ->with(self::TEST_ENTITY_REFERENCE)
            ->willReturn(false);

        $this->assertFalse($this->filter->isEntityAuditable(new LoggableClass(), false));
    }

    public function testIsEntityAuditable()
    {
        $config = new Config(new EntityConfigId('dataaudit', self::TEST_ENTITY_REFERENCE));
        $config->set('auditable', true);

        $this->configProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::TEST_ENTITY_REFERENCE)
            ->willReturn(true);
        $this->configProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::TEST_ENTITY_REFERENCE)
            ->willReturn($config);

        $this->assertTrue(
            $this->filter->isEntityAuditable(new LoggableClass(), false)
        );
    }
}
