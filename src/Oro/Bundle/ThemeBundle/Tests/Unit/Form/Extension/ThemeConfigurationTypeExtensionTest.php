<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Oro\Bundle\ThemeBundle\Form\EventListener\ThemeConfigurationSubscriber;
use Oro\Bundle\ThemeBundle\Form\Extension\ThemeConfigurationTypeExtension;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Form\Type\ConfigurationType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeConfigurationType;
use Oro\Bundle\ThemeBundle\Form\Type\ThemeSelectType;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationTypeProvider;
use Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type\Stub\ThemeSelectTypeStub;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class ThemeConfigurationTypeExtensionTest extends FormIntegrationTestCase
{
    private ThemeConfigurationTypeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $themeConfigurationProvider = $this->createMock(ThemeConfigurationProvider::class);
        $themeConfigurationSubscriber = new ThemeConfigurationSubscriber($themeConfigurationProvider);
        $this->type = new ThemeConfigurationType($themeConfigurationSubscriber);

        $this->extension = new ThemeConfigurationTypeExtension(
            $this->createMock(ThemeConfigurationTypeProvider::class)
        );

        parent::setUp();
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
                [
                    ThemeConfigurationType::class => [$this->extension],
                ]
            ),
        ];
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([ThemeConfigurationType::class], ThemeConfigurationTypeExtension::getExtendedTypes());
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(ThemeConfigurationType::class, $this->createDefaultThemeConfigurationEntity());

        self::assertTrue($form->has('type'));
    }

    private function createDefaultThemeConfigurationEntity(): ThemeConfiguration
    {
        return (new ThemeConfiguration())
            ->setName('Default Configuration')
            ->setDescription('Default Configuration Description')
            ->setTheme('default')
            ->setConfiguration([]);
    }
}
