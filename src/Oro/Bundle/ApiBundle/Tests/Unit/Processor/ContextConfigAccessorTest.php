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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContextConfigAccessorTest extends TestCase
{
    private Context&MockObject $context;
    private ContextConfigAccessor $configAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->configAccessor = new ContextConfigAccessor($this->context);
    }

    public function testGetConfigForContextClass(): void
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

    public function testGetConfigForContextClassForCaseWhenApiResourceIsBasedOnManageableEntity(): void
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

    public function testGetConfigForNotContextClass(): void
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

    public function testGetConfigForForFormDataClass(): void
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

    public function testGetConfigForNotFormDataClass(): void
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
