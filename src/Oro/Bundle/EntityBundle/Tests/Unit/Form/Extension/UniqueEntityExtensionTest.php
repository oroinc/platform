<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\Form\Extension\UniqueEntityExtension;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class UniqueEntityExtensionTest extends TestCase
{
    private const ENTITY = 'Namespace\EntityName';

    private ValidatorInterface&MockObject $validator;
    private ConfigProvider&MockObject $entityConfigProvider;
    private ConfigProvider&MockObject $extendConfigProvider;
    private DoctrineHelper&MockObject $doctrineHelper;
    private ConfigInterface&MockObject $entityConfig;
    private ConfigInterface&MockObject $extendConfig;
    private ClassMetadata&MockObject $validatorMetadata;
    private FormBuilder&MockObject $builder;
    private UniqueEntityExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityConfig = $this->createMock(ConfigInterface::class);
        $this->extendConfig = $this->createMock(ConfigInterface::class);
        $this->validatorMetadata = $this->createMock(ClassMetadata::class);
        $this->builder = $this->createMock(FormBuilder::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(fn (string $id): string => $id . '.translated');

        $this->extension = new UniqueEntityExtension(
            $this->validator,
            $translator,
            $this->entityConfigProvider,
            $this->doctrineHelper
        );
        $this->extension->setExtendConfigProvider($this->extendConfigProvider);
    }

    public function testWithoutClass(): void
    {
        $this->validatorMetadata->expects($this->never())
            ->method('addConstraint');

        $this->extension->buildForm($this->builder, []);
    }

    public function testForNotManageableEntity(): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with(self::ENTITY)
            ->willReturn(false);

        $this->extendConfigProvider->expects($this->never())
            ->method('hasConfig');

        $this->validatorMetadata->expects($this->never())
            ->method('addConstraint');

        $this->extension->buildForm($this->builder, ['data_class' => self::ENTITY]);
    }

    public function testWithoutConfig(): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with(self::ENTITY)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY)
            ->willReturn(false);

        $this->validatorMetadata->expects($this->never())
            ->method('addConstraint');

        $this->extension->buildForm($this->builder, ['data_class' => self::ENTITY]);
    }

    public function testWithoutUniqueKeyOption(): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with(self::ENTITY)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY)
            ->willReturn($this->extendConfig);

        $this->extendConfig->expects($this->once())
            ->method('get')
            ->with('unique_key')
            ->willReturn(null);

        $this->validatorMetadata->expects($this->never())
            ->method('addConstraint');

        $this->extension->buildForm($this->builder, ['data_class' => self::ENTITY]);
    }

    public function testWithConfigAndKeys(): void
    {
        $this->doctrineHelper->expects($this->once())
            ->method('isManageableEntity')
            ->with(self::ENTITY)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('hasConfig')
            ->with(self::ENTITY)
            ->willReturn(true);

        $this->extendConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY)
            ->willReturn($this->extendConfig);

        $this->extendConfig->expects($this->once())
            ->method('get')
            ->with('unique_key')
            ->willReturn(['keys' => ['tag0' => ['name' => 'test', 'key' => ['field']]]]);

        $this->entityConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with(self::ENTITY, 'field')
            ->willReturn($this->entityConfig);

        $this->entityConfig->expects($this->once())
            ->method('get')
            ->with('label')
            ->willReturn('label');

        $this->validator->expects($this->once())
            ->method('getMetadataFor')
            ->with(self::ENTITY)
            ->willReturn($this->validatorMetadata);

        $this->validatorMetadata->expects($this->once())
            ->method('addConstraint');

        $this->extension->buildForm($this->builder, ['data_class' => self::ENTITY]);
    }
}
