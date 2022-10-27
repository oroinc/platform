<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\MappingException;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type\AbstractConfigTypeTestCase;
use Oro\Bundle\ImportExportBundle\Form\Type\IdentityConfigChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class IdentityConfigChoiceTypeTest extends AbstractConfigTypeTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var IdentityConfigChoiceType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->type = new IdentityConfigChoiceType($this->typeHelper, $this->registry);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        bool $immutable,
        array $options,
        array $expectedOptions
    ) {
        $this->mockRegistry();
        $this->doTestConfigureOptions($this->type, $configId, $immutable, $options, $expectedOptions);
    }

    public function testValueShouldBeReadOnly()
    {
        $this->mockRegistry();
        $resolvedOptions = $this->resolveOptions();

        $this->assertTrue($resolvedOptions['disabled']);
    }

    public function testDefaultsChoices()
    {
        $this->mockRegistry();
        $expectedChoices = [
            'oro.importexport.entity_config.identity.no' => IdentityConfigChoiceType::CHOICE_NO,
            'oro.importexport.entity_config.identity.only_when_not_empty' =>
                IdentityConfigChoiceType::CHOICE_WHEN_NOT_EMPTY,
            'oro.importexport.entity_config.identity.always' => IdentityConfigChoiceType::CHOICE_ALWAYS
        ];
        $resolvedOptions = $this->resolveOptions();

        $this->assertEquals($expectedChoices, $resolvedOptions['choices']);
    }

    private function resolveOptions(): array
    {
        $this->typeHelper->expects($this->never())
            ->method('getFieldName');
        $this->typeHelper->expects($this->never())
            ->method('isImmutable');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'config_id' => new FieldConfigId('importexport', 'Test\Entity', 'id')
        ];

        return $resolver->resolve($options);
    }

    public function testPreSetDataListener()
    {
        $this->mockRegistry();
        $options = [
            'config_id' => new FieldConfigId('importexport', 'Test\Entity', 'id')
        ];

        $config = $this->createMock(FormConfigInterface::class);
        $config->expects($this->once())
            ->method('getOptions')
            ->willReturn($options);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $event = new FormEvent($form, IdentityConfigChoiceType::CHOICE_NO);

        $this->type->onPreSetData($event);

        $this->assertEquals(IdentityConfigChoiceType::CHOICE_ALWAYS, $event->getData());
    }

    public function testMappingException()
    {
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->willThrowException(new MappingException());

        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);

        $this->typeHelper->expects($this->once())
            ->method('isImmutable')
            ->willReturn(false);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [
            'config_id' => new FieldConfigId('importexport', 'Test\Entity', 'id')
        ];

        $resolvedOptions = $resolver->resolve($options);

        $this->assertFalse($resolvedOptions['disabled']);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_importexport_identity_config_choice', $this->type->getName());
    }

    /**
     * Set ID as entity identifier
     */
    private function mockRegistry()
    {
        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $this->registry->expects($this->any())
            ->method('getManager')
            ->willReturn($entityManager);
    }
}
