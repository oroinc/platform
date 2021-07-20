<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Manager;

use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EmbeddedFormManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithContainerAndFormFactory()
    {
        new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
    }

    /**
     * @test
     */
    public function shouldCreateForm()
    {
        $type = 'type';
        $formRegistry = $this->createFormRegistryMock();
        $formFactory = $this->createFormFactoryMock();
        $manager = new EmbeddedFormManager($formRegistry, $formFactory);

        $formInstance = new \stdClass();

        $formFactory->expects($this->once())
            ->method('create')
            ->with($type, null, [])
            ->will($this->returnValue($formInstance));

        $this->assertSame($formInstance, $manager->createForm($type));
    }

    /**
     * @test
     */
    public function shouldAllowToAddFormType()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $type = uniqid();
        $manager->addFormType($type);
    }

    /**
     * @test
     */
    public function shouldAllowToAddFormTypeWithLabel()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $type = uniqid();
        $label = uniqid('label');
        $manager->addFormType($type, $label);
    }

    /**
     * @test
     */
    public function shouldReturnEmptyLabelForNotAddedType()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $type = uniqid();
        $this->assertNull($manager->getLabelByType($type));
    }

    /**
     * @test
     */
    public function shouldReturnLabelForAddedType()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $type = uniqid();
        $label = uniqid('label');
        $manager->addFormType($type, $label);
        $this->assertEquals($label, $manager->getLabelByType($type));
    }

    /**
     * @test
     */
    public function shouldReturnTypeAsLabelForAddedTypeWithoutLabel()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $type = uniqid();
        $manager->addFormType($type);
        $this->assertEquals($type, $manager->getLabelByType($type));
    }

    /**
     * @test
     */
    public function shouldReturnAllAddedTypes()
    {
        $types = [
            $type1 = uniqid('type') => uniqid('label'),
            $type2 = uniqid('type') => uniqid('label'),
        ];
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $manager->addFormType($type1, $types[$type1]);
        $manager->addFormType($type2, $types[$type2]);

        $this->assertEquals($types, $manager->getAll());
    }

    /**
     * @test
     */
    public function shouldReturnEmptyDefaultCss()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $this->assertEquals('', $manager->getDefaultCssByType(uniqid('type')));
    }

    /**
     * @test
     */
    public function shouldReturnDefaultCss()
    {
        $type = 'type';
        $typeInstance = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface');
        $formRegistry = $this->createFormRegistryMock($typeInstance);
        $formFactory = $this->createFormFactoryMock();
        $defaultCss = 'my default css';

        $typeInstance->expects($this->once())
            ->method('getDefaultCss')
            ->will($this->returnValue($defaultCss));

        $manager = new EmbeddedFormManager($formRegistry, $formFactory);
        $this->assertEquals($defaultCss, $manager->getDefaultCssByType($type));
    }

    /**
     * @test
     */
    public function shouldReturnDefaultSuccessMessage()
    {
        $type = 'type';
        $typeInstance = $this->createMock('Oro\Bundle\EmbeddedFormBundle\Form\Type\EmbeddedFormInterface');
        $formRegistry = $this->createFormRegistryMock($typeInstance);
        $formFactory = $this->createFormFactoryMock();
        $defaultMessage = 'my default message';

        $typeInstance->expects($this->once())
            ->method('getDefaultSuccessMessage')
            ->will($this->returnValue($defaultMessage));

        $manager = new EmbeddedFormManager($formRegistry, $formFactory);
        $this->assertEquals($defaultMessage, $manager->getDefaultSuccessMessageByType($type));
    }

    /**
     * @test
     */
    public function shouldReturnEmptyDefaultSuccessMessage()
    {
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $this->assertEquals('', $manager->getDefaultSuccessMessageByType(uniqid('type')));
    }

    public function testGetAllChoices()
    {
        $type1 = 'type1';
        $typeLabel1 = 'Type 1';
        $type2 = 'type2';
        $typeLabel2 = 'Type 2';
        $manager = new EmbeddedFormManager($this->createFormRegistryMock(), $this->createFormFactoryMock());
        $manager->addFormType($type1, $typeLabel1);
        $manager->addFormType($type2, $typeLabel2);

        $this->assertEquals([$typeLabel1 => $type1, $typeLabel2 => $type2], $manager->getAllChoices());
    }

    public function testGetTypeInstance()
    {
        $manager = new EmbeddedFormManager(
            $this->createFormRegistryMock(IntegerType::class),
            $this->createFormFactoryMock()
        );
        $this->assertEquals(null, $manager->getTypeInstance(null));
        $this->assertEquals(IntegerType::class, $manager->getTypeInstance(AbstractType::class));
    }

    /**
     * @return FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFormFactoryMock()
    {
        return $this->createMock(FormFactoryInterface::class);
    }

    /**
     * @param null|object $typeInstance
     * @return FormRegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createFormRegistryMock($typeInstance = null)
    {
        $fromRegistry = $this->createMock(FormRegistryInterface::class);

        if ($typeInstance) {
            $resolvedFormType = $this->createMock(ResolvedFormTypeInterface::class);
            $resolvedFormType->expects($this->once())
                ->method('getInnerType')
                ->willReturn($typeInstance);
            $fromRegistry->expects($this->once())
                ->method('getType')
                ->willReturn($resolvedFormType);
        }

        return $fromRegistry;
    }
}
