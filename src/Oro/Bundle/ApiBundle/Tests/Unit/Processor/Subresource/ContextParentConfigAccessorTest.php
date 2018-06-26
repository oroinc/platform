<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentConfigAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;

class ContextParentConfigAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SubresourceContext */
    private $context;

    /** @var ContextParentConfigAccessor */
    private $configAccessor;

    protected function setUp()
    {
        $this->context = $this->createMock(SubresourceContext::class);

        $this->configAccessor = new ContextParentConfigAccessor($this->context);
    }

    public function testGetConfigForContextParentClass()
    {
        $className = User::class;
        $config = new EntityDefinitionConfig();

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getParentConfig')
            ->willReturn($config);

        self::assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForContextParentClassForCaseWhenParentApiResourceIsBasedOnManageableEntity()
    {
        $className = User::class;
        $config = new EntityDefinitionConfig();

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(UserProfile::class);
        $this->context->expects(self::once())
            ->method('getParentConfig')
            ->willReturn($config);

        self::assertSame($config, $this->configAccessor->getConfig($className));
    }

    public function testGetConfigForNotContextParentClass()
    {
        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(User::class);
        $this->context->expects(self::never())
            ->method('getParentConfig');

        self::assertNull($this->configAccessor->getConfig(Product::class));
    }
}
