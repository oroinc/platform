<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager\Stub\EmbeddedFormTypeStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmbeddedFormManagerTest extends TestCase
{
    private FormRegistryInterface&MockObject $formRegistry;
    private FormFactoryInterface&MockObject $formFactory;
    private EmbeddedFormManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->formRegistry = $this->createMock(FormRegistryInterface::class);
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->manager = new EmbeddedFormManager($this->formRegistry, $this->formFactory);
    }

    public function testShouldCreateForm(): void
    {
        $type = 'type';

        $formInstance = $this->createMock(FormInterface::class);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with($type, null, [])
            ->willReturn($formInstance);

        $this->assertSame($formInstance, $this->manager->createForm($type));
    }

    public function testShouldReturnEmptyLabelForNotAddedType(): void
    {
        $type = 'Test\Type';
        $this->assertNull($this->manager->getLabelByType($type));
    }

    public function testShouldReturnLabelForAddedType(): void
    {
        $type = 'Test\Type';
        $label = 'test_label';
        $this->manager->addFormType($type, $label);
        $this->assertEquals($label, $this->manager->getLabelByType($type));
    }

    public function testShouldReturnTypeAsLabelForAddedTypeWithoutLabel(): void
    {
        $type = 'Test\Type';
        $this->manager->addFormType($type);
        $this->assertEquals($type, $this->manager->getLabelByType($type));
    }

    public function testShouldReturnAllAddedTypes(): void
    {
        $types = [
            $type1 = 'Test\Type1' => 'test_label_1',
            $type2 = 'Test\Type2' => 'test_label_2'
        ];
        $this->manager->addFormType($type1, $types[$type1]);
        $this->manager->addFormType($type2, $types[$type2]);

        $this->assertEquals($types, $this->manager->getAll());
    }

    public function testShouldReturnEmptyDefaultCss(): void
    {
        $this->assertEquals('', $this->manager->getDefaultCssByType('Test\Type'));
    }

    public function testShouldReturnDefaultCss(): void
    {
        $type = 'type';
        $typeInstance = $this->createMock(EmbeddedFormTypeStub::class);
        $defaultCss = 'my default css';

        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedFormType->expects($this->once())
            ->method('getInnerType')
            ->willReturn($typeInstance);
        $this->formRegistry->expects($this->once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $typeInstance->expects($this->once())
            ->method('getDefaultCss')
            ->willReturn($defaultCss);

        $this->assertEquals($defaultCss, $this->manager->getDefaultCssByType($type));
    }

    public function testShouldReturnDefaultSuccessMessage(): void
    {
        $type = 'type';
        $typeInstance = $this->createMock(EmbeddedFormTypeStub::class);
        $defaultMessage = 'my default message';

        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedFormType->expects($this->once())
            ->method('getInnerType')
            ->willReturn($typeInstance);
        $this->formRegistry->expects($this->once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $typeInstance->expects($this->once())
            ->method('getDefaultSuccessMessage')
            ->willReturn($defaultMessage);

        $this->assertEquals($defaultMessage, $this->manager->getDefaultSuccessMessageByType($type));
    }

    public function testShouldReturnEmptyDefaultSuccessMessage(): void
    {
        $this->assertEquals('', $this->manager->getDefaultSuccessMessageByType('Test\Type'));
    }

    public function testGetAllChoices(): void
    {
        $type1 = 'type1';
        $typeLabel1 = 'Type 1';
        $type2 = 'type2';
        $typeLabel2 = 'Type 2';
        $this->manager->addFormType($type1, $typeLabel1);
        $this->manager->addFormType($type2, $typeLabel2);

        $this->assertEquals([$typeLabel1 => $type1, $typeLabel2 => $type2], $this->manager->getAllChoices());
    }

    public function testGetTypeInstance(): void
    {
        $innerType = $this->createMock(FormTypeInterface::class);
        $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
        $resolvedFormType->expects($this->once())
            ->method('getInnerType')
            ->willReturn($innerType);
        $this->formRegistry->expects($this->once())
            ->method('getType')
            ->willReturn($resolvedFormType);

        $this->assertEquals(null, $this->manager->getTypeInstance(null));
        $this->assertEquals($innerType, $this->manager->getTypeInstance(AbstractType::class));
    }
}
