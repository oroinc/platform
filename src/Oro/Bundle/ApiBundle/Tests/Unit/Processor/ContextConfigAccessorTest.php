<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;

class ContextConfigAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Context */
    private $context;

    /** @var ContextConfigAccessor */
    private $configAccessor;

    protected function setUp()
    {
        $this->context = $this->createMock(Context::class);

        $this->configAccessor = new ContextConfigAccessor($this->context);
    }

    public function testGetConfigForContextClass()
    {
        $className = User::class;
        $config = new EntityDefinitionConfig();

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForContextClassForCaseWhenApiResourceIsBasedOnManageableEntity()
    {
        $className = User::class;
        $config = new EntityDefinitionConfig();

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn(UserProfile::class);
        $this->context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForNotContextClass()
    {
        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn(User::class);
        $this->context->expects(self::never())
            ->method('getConfig');

        self::assertNull($this->configAccessor->getConfig(Product::class));
    }
}
