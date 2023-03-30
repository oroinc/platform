<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Provider\FormFieldsMapProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FormFieldsMapProviderTest extends TestCase
{
    private ManagerRegistry|MockObject $managerRegistry;

    private EntityManagerInterface|MockObject $entityManager;

    private FormFieldsMapProvider $fieldsMapProvider;

    private ClassMetadata|MockObject $classMetadata;

    private FormConfigInterface|MockObject $formConfig;

    protected function setUp(): void
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->formConfig = $this->createMock(FormConfigInterface::class);
        $this->fieldsMapProvider = new FormFieldsMapProvider($this->managerRegistry);
    }

    public function testGetScalarFieldsMapWithEmptyDataClass(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, []);
        self::assertEquals([], $result);
    }

    public function testGetScalarFieldsMapWithoutEntityManagerClass(): void
    {
        $dataClass = 'TestClass';
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this
            ->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($dataClass)
            ->willReturn(null);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, ['data_class' => $dataClass]);
        self::assertEquals([], $result);
    }

    public function testGetScalarFieldsMapWithEmptyForm(): void
    {
        $dataClass = 'TestClass';
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $this
            ->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($dataClass)
            ->willReturn(null);
        $this
            ->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($dataClass)
            ->willReturn($this->entityManager);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, ['data_class' => $dataClass]);
        self::assertEquals([], $result);
    }

    public function testGetFormFieldsWhenNotMapped(): void
    {
        $dataClass = 'TestClass';
        $viewName = 'testName';
        $viewFullName = 'testFullName';
        $viewId = 'testId';
        $view = $this->getFormView($viewName, $viewFullName, $viewId);
        $form = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);

        $this->formConfig
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn(['mapped' => false]);

        $childForm
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $form->expects(self::once())
            ->method('get')
            ->with($viewName)
            ->willReturn($childForm);

        $this->classMetadata
            ->expects(self::never())
            ->method('getTypeOfField');

        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($dataClass)
            ->willReturn($this->classMetadata);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($dataClass)
            ->willReturn($this->entityManager);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, ['data_class' => $dataClass]);
        self::assertEquals([], $result);
    }

    public function testGetFormFieldsWhenMappedAndNotDynamic(): void
    {
        $dataClass = 'TestClass';
        $viewName = 'testName';
        $viewFullName = 'testFullName';
        $viewId = 'testId';
        $view = $this->getFormView($viewName, $viewFullName, $viewId);
        $form = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);

        $this->formConfig
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn(['mapped' => true]);

        $childForm
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $form->expects(self::once())
            ->method('get')
            ->with($viewName)
            ->willReturn($childForm);

        $this->classMetadata
            ->expects(self::never())
            ->method('getTypeOfField');

        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($dataClass)
            ->willReturn($this->classMetadata);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($dataClass)
            ->willReturn($this->entityManager);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, ['data_class' => $dataClass]);
        self::assertEquals(
            [
                'testName' => [
                    'id' => 'testId',
                    'key' => 'testName',
                    'name' => 'testFullName',
                ],
            ],
            $result
        );
    }

    /**
     * @dataProvider formFieldsDataProvider
     */
    public function testGetFormFieldsWhenMappedAndDynamic(string $type): void
    {
        $dataClass = 'TestClass';
        $viewName = 'testName';
        $viewFullName = 'testFullName';
        $viewId = 'testId';
        $view = $this->getFormView($viewName, $viewFullName, $viewId);
        $form = $this->createMock(FormInterface::class);
        $childForm = $this->createMock(FormInterface::class);

        $this->formConfig
            ->expects(self::once())
            ->method('getOptions')
            ->willReturn(['mapped' => true, 'is_dynamic_field' => true]);

        $childForm
            ->expects(self::once())
            ->method('getConfig')
            ->willReturn($this->formConfig);

        $form->expects(self::once())
            ->method('get')
            ->with($viewName)
            ->willReturn($childForm);

        $this->classMetadata
            ->expects(self::once())
            ->method('getTypeOfField')
            ->with($viewName)
            ->willReturn($type);

        $this->entityManager
            ->expects(self::once())
            ->method('getClassMetadata')
            ->with($dataClass)
            ->willReturn($this->classMetadata);

        $this->managerRegistry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with($dataClass)
            ->willReturn($this->entityManager);

        $result = $this->fieldsMapProvider->getFormFieldsMap($view, $form, ['data_class' => $dataClass]);
        self::assertEquals(
            [
                'testName' => [
                    'id' => 'testId',
                    'key' => 'testName',
                    'name' => 'testFullName',
                ],
            ],
            $result
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function formFieldsDataProvider(): array
    {
        return [
            'integerType' => [
                'type' => Types::INTEGER,
            ],
            'smallintType' => [
                'type' => Types::SMALLINT,
            ],
            'bigintType' => [
                'type' => Types::BIGINT,
            ],
            'floatType' => [
                'type' => Types::FLOAT,
            ],
            'decimalType' => [
                'type' => Types::DECIMAL,
            ],
            'booleanType' => [
                'type' => Types::BOOLEAN,
            ],
            'stringType' => [
                'type' => Types::STRING,
            ],
            'textType' => [
                'type' => Types::TEXT,
            ],
            'asciiStringType' => [
                'type' => Types::ASCII_STRING,
            ],
        ];
    }

    private function getFormView(string $name, string $fullName, string $id): FormView
    {
        $view = new FormView();
        $childView = new FormView();
        $childView->vars = [
            'full_name' => $fullName,
            'id' => $id,
        ];

        $view->children = [$name => $childView];

        return $view;
    }
}
