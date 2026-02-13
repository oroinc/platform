<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\EventListener\ThemeConfigurationSubscriber;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeSelectType;
use Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type\Stub\ThemeSelectTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;

/**
 * Covers ThemeConfigurationType form type by Unit Tests
 */
final class ThemeConfigurationTypeTest extends FormIntegrationTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $themeConfigurationSubscriber = new ThemeConfigurationSubscriber($themeConfigurationProvider);
        $this->type = new ThemeConfigurationType($themeConfigurationSubscriber);

        parent::setUp();
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(
            ThemeConfigurationType::class,
            $this->createDefaultThemeConfigurationEntity()
        );

        self::assertTrue($form->has('name'));
        self::assertTrue($form->has('description'));
        self::assertTrue($form->has('theme'));
        self::assertTrue($form->has('configuration'));
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

        self::assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);

        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());

        $data = $form->getData();

        self::assertEquals($expectedData, $data);
    }

    public function submitProvider(): array
    {
        $submittedData = [
            [
                'name' => 'Updated Configuration',
                'description' => 'Updated Configuration Description',
                'theme' => 'custom',
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

        self::assertEquals($default, $form->getData());

        $form->submit(
            [
                'name' => 'Updated Configuration',
                'description' => 'Updated Configuration Description',
                'theme' => 'not_exits',
                'configuration' => []
            ]
        );

        self::assertFalse($form->isValid());
        self::assertTrue($form->isSynchronized());

        $data = $form->getData();

        self::assertEquals('default', $data->getTheme());
    }

    public function testNameIsEmpty(): void
    {
        $default = $this->createDefaultThemeConfigurationEntity();
        $form = $this->factory->create(ThemeConfigurationType::class, $default);

        self::assertEquals($default, $form->getData());
        self::expectException(InvalidArgumentException::class);

        $form->submit(
            [
                'name' => '',
                'description' => 'Updated Configuration Description',
                'theme' => 'custom',
                'configuration' => []
            ]
        );
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $configurationChildBuilder = $this->createMock(ConfigurationBuildersProvider::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    ThemeSelectType::class => new ThemeSelectTypeStub(),
                    new ConfigurationType($configurationChildBuilder)
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
        $themeConfiguration->setConfiguration($submittedData['configuration']);

        return $themeConfiguration;
    }
}
