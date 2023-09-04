<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ContextConfigAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Contact;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Group;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;

class ContextConfigAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var Context|\PHPUnit\Framework\MockObject\MockObject */
    private $context;

    /** @var ContextConfigAccessor */
    private $configAccessor;

    protected function setUp(): void
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
        $config = new EntityDefinitionConfig();

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn(User::class);
        $this->context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertNull($this->configAccessor->getConfig(Product::class));
    }

    public function testGetConfigForForFormDataClass()
    {
        $className = User::class;
        $formDataClass = Contact::class;
        $config = new EntityDefinitionConfig();
        $config->setFormOption('data_class', $formDataClass);

        $this->context->expects(self::exactly(2))
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertSame($config, $this->configAccessor->getConfig($formDataClass));
    }

    public function testGetConfigForNotFormDataClass()
    {
        $className = User::class;
        $formDataClass = Contact::class;
        $config = new EntityDefinitionConfig();
        $config->setFormOption('data_class', $formDataClass);

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);

        self::assertNull($this->configAccessor->getConfig(Group::class));
    }
}
