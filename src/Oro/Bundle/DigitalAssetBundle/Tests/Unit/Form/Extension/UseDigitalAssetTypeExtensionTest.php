<?php

namespace Oro\Bundle\DigitalAssetBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use Oro\Bundle\DigitalAssetBundle\Form\Extension\UseDigitalAssetTypeExtension;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UseDigitalAssetTypeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var UseDigitalAssetTypeExtension */
    private $extension;

    protected function setUp()
    {
        $this->entityConfigManager = $this->createMock(ConfigManager::class);

        $this->extension = new UseDigitalAssetTypeExtension($this->entityConfigManager);

        parent::setUp();
    }

    public function testGetExtendedTypes(): void
    {
        $this->assertEquals([FileType::class, ImageType::class], UseDigitalAssetTypeExtension::getExtendedTypes());
    }

    public function testConfiguraOptions(): void
    {
        $resolver = $this->createMock(OptionsResolver::class);

        $resolver
            ->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'dam_widget_enabled' => true,
                    'dam_widget_route' => 'oro_digital_asset_select_widget',
                    'dam_widget_parameters' => [],
                ]
            );

        $this->extension->configureOptions($resolver);
    }

    public function testBuildForm(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder
            ->expects($this->once())
            ->method('add')
            ->with('dam_file', HiddenType::class, ['mapped' => false]);

        $this->extension->buildForm($builder, []);
    }

    public function testBuildViewWhenDamWidgetIsAlreadyAdded(): void
    {
        $formView = new FormView();
        $formView->vars['dam_widget'] = [];

        $this->extension->buildView(
            $formView,
            $this->createMock(FormInterface::class),
            []
        );

        $this->assertArrayNotHasKey('route', $formView->vars['dam_widget']);
    }

    public function testBuildViewWhenDamDisabled(): void
    {
        $this->extension->buildView(
            $formView = new FormView(),
            $this->createMock(FormInterface::class),
            ['dam_widget_enabled' => false]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoPropertyPath(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenMultiplePropertyPath(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(2);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoParent(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoParentDataClass(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoFieldName(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn($entityClass = 'SampleClass');

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn('');

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenNoFieldConfig(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn($entityClass = 'SampleClass');

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($fieldName = 'sampleField');

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn(null);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildViewWhenDamDisabledInConfig(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn($entityClass = 'SampleClass');

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($fieldName = 'sampleField');

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(false);

        $this->extension->buildView(
            $formView = new FormView(),
            $form,
            ['dam_widget_enabled' => true]
        );

        $this->assertArrayNotHasKey('dam_widget', $formView->vars);
    }

    public function testBuildView(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('getPropertyPath')
            ->willReturn($propertyPath = $this->createMock(PropertyPathInterface::class));

        $propertyPath
            ->expects($this->once())
            ->method('getLength')
            ->willReturn(1);

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm = $this->createMock(FormInterface::class));

        $parentForm
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($parentFormConfig = $this->createMock(FormConfigInterface::class));

        $parentFormConfig
            ->expects($this->once())
            ->method('getDataClass')
            ->willReturn($entityClass = 'SampleClass');

        $propertyPath
            ->expects($this->once())
            ->method('__toString')
            ->willReturn($fieldName = 'sampleField');

        $this->entityConfigManager
            ->expects($this->once())
            ->method('getFieldConfig')
            ->with('attachment', $entityClass, $fieldName)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $config
            ->expects($this->once())
            ->method('is')
            ->with('use_dam')
            ->willReturn(true);

        $formView = new FormView();
        $formView->vars['block_prefixes'] = ['sample1', 'sample2'];
        $this->extension->buildView(
            $formView,
            $form,
            $options = [
                'dam_widget_enabled' => true,
                'dam_widget_route' => 'sample_route',
                'dam_widget_parameters' => ['sample_params'],
            ]
        );

        $this->assertArrayHasKey('dam_widget', $formView->vars);
        $this->assertEquals(
            [
                'route' => $options['dam_widget_route'],
                'parameters' => $options['dam_widget_parameters'],
            ],
            $formView->vars['dam_widget']
        );
        $this->assertEquals(['sample1', 'oro_file_with_digital_asset', 'sample2'], $formView->vars['block_prefixes']);
    }
}
