<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\ThemeSelectType;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Entity\Enum\ThemeConfigurationType as ThemeConfigurationTypeOptions;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\EventListener\ThemeConfigurationSubscriber;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type\Stub\ThemeSelectTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

/**
 * Covers ThemeConfigurationType form type by Unit Tests
 */
final class ThemeConfigurationTypeTest extends FormIntegrationTestCase
{
    protected function setUp(): void
    {
        $this->themeConfigurationSubscriber = $this->createMock(ThemeConfigurationSubscriber::class);
        $this->type = new ThemeConfigurationType($this->themeConfigurationSubscriber);

        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(
            ThemeConfigurationType::class,
            $this->createDefaultThemeConfigurationEntity()
        );

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('theme'));
        $this->assertTrue($form->has('type'));
        $this->assertTrue($form->has('configuration'));
    }

    /**
     * @dataProvider submitProvider
     */
    public function testSubmit(
        ThemeConfiguration $defaultData,
        array $submittedData,
        ThemeConfiguration $expectedData
    ): void {
        $form = $this->factory->create(ThemeConfigurationType::class, $defaultData);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals($expectedData, $data);
    }

    public function submitProvider(): array
    {
        $submittedData = [
            [
                'name' => 'Updated Configuration',
                'description' => 'Updated Configuration Description',
                'theme' => 'custom',
                'type' => ThemeConfigurationTypeOptions::Storefront->value,
                'configuration' => []
            ]
        ];

        return [
            'test submit' => [
                'defaultData' => $this->createDefaultThemeConfigurationEntity(),
                'submittedData' => $submittedData[0],
                'expectedData' => $this->createThemeConfigurationFromSubmittedData($submittedData[0]),
            ]
        ];
    }

    public function testThemeNotValid(): void
    {
        $default = $this->createDefaultThemeConfigurationEntity();
        $form = $this->factory->create(ThemeConfigurationType::class, $default);

        $this->assertEquals($default, $form->getData());
        $form->submit(
            [
                'name' => 'Updated Configuration',
                'description' => 'Updated Configuration Description',
                'theme' => 'not_exits',
                'type' => ThemeConfigurationTypeOptions::Storefront->value,
                'configuration' => []
            ]
        );
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals('default', $data->getTheme());
    }

    public function testTypeNotValid(): void
    {
        $default = $this->createDefaultThemeConfigurationEntity();
        $form = $this->factory->create(ThemeConfigurationType::class, $default);

        $this->assertEquals($default, $form->getData());
        $form->submit(
            [
                'name' => 'Updated Configuration',
                'description' => 'Updated Configuration Description',
                'theme' => 'custom',
                'type' => 'not_valid',
                'configuration' => []
            ]
        );
        $this->assertFalse($form->isValid());
        $this->assertTrue($form->isSynchronized());

        $data = $form->getData();

        $this->assertEquals('custom', $data->getTheme());
        $this->assertEquals(ThemeConfigurationTypeOptions::Storefront, $data->getType());
    }

    public function testNameIsEmpty(): void
    {
        $default = $this->createDefaultThemeConfigurationEntity();
        $form = $this->factory->create(ThemeConfigurationType::class, $default);

        $this->assertEquals($default, $form->getData());
        $this->expectException(InvalidArgumentException::class);
        $form->submit(
            [
                'name' => '',
                'description' => 'Updated Configuration Description',
                'theme' => 'custom',
                'type' => ThemeConfigurationTypeOptions::Storefront->value,
                'configuration' => []
            ]
        );
    }

    protected function getExtensions(): array
    {
        $themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $themeConfigurationSubscriber = new ThemeConfigurationSubscriber($themeConfigurationProvider);
        $type = new ThemeConfigurationType($themeConfigurationSubscriber);

        $configurationChildBuilder = $this->createMock(ConfigurationBuildersProvider::class);
        $configurationType = new ConfigurationType($configurationChildBuilder);

        return [
            new PreloadedExtension(
                [
                    $type,
                    ThemeSelectType::class => new ThemeSelectTypeStub(),
                    $configurationType
                ],
                []
            ),
        ];
    }

    private function createDefaultThemeConfigurationEntity(): ThemeConfiguration
    {
        return (new ThemeConfiguration())
            ->setName('Default Configuration')
            ->setDescription('Default Configuration Description')
            ->setTheme('default')
            ->setConfiguration([]);
    }

    private function createThemeConfigurationFromSubmittedData(array $submittedData): ThemeConfiguration
    {
        $themeConfiguration = $this->createDefaultThemeConfigurationEntity();

        $themeConfiguration->setName($submittedData['name']);
        $themeConfiguration->setDescription($submittedData['description']);
        $themeConfiguration->setTheme($submittedData['theme']);
        $themeConfiguration->setType(ThemeConfigurationTypeOptions::from($submittedData['type']));
        $themeConfiguration->setConfiguration($submittedData['configuration']);

        return $themeConfiguration;
    }
}
