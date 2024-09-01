<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Validator;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle\TestThemeConfigurationBundle;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle2\TestThemeConfiguration2Bundle;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle3\TestThemeConfiguration3Bundle;
use Oro\Bundle\ThemeBundle\Validator\ConfigurationValidator;
use Oro\Bundle\ThemeBundle\Validator\PreviewConfigurationValidator;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

final class ConfigurationValidatorTest extends TestCase
{
    use TempDirExtension;

    private ConfigurationValidator $configurationValidator;

    protected function setUp(): void
    {
        $configurationBuildersProvider = $this->createMock(ConfigurationBuildersProvider::class);
        $configurationBuildersProvider->expects(self::any())
            ->method('getConfigurationTypes')
            ->willReturn(['select', 'radio', 'checkbox']);

        $this->configurationValidator = new ConfigurationValidator(
            new ThemeConfigurationProvider(
                $this->getTempFile('ConfigurationProvider'),
                false,
                new ThemeConfiguration($configurationBuildersProvider),
                '[a-zA-Z][a-zA-Z0-9_\-:]*'
            ),
            new \ArrayIterator([
                new PreviewConfigurationValidator(['png', 'jpg'])
            ])
        );
    }

    public function testThemeConfigurationShouldBeValid(): void
    {
        $bundle = new TestThemeConfigurationBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle->getName() => get_class($bundle)
            ]);

        $messages = $this->configurationValidator->validate();

        self::assertEquals([], $messages);
    }

    public function testThemeConfigurationImagesAreMissOrHasWrongExtension(): void
    {
        $bundle = new TestThemeConfiguration2Bundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle->getName() => get_class($bundle)
            ]);

        $result = $this->configurationValidator->validate();

        self::assertEquals(
            [
                'configuration.sections.general.options.palette.previews.traditional. The preview file'
                . ' bundles/testthemeconfiguration2/images/previews/tradition-pallete.jpg does not exist'
                . ' or the extension is not supported. Supported extensions are [png, jpg].',
                'configuration.sections.general.options.rounding.previews.checked. The preview file'
                . ' bundles/testthemeconfiguration2/images/previews/rounded-controls.png does not exist'
                . ' or the extension is not supported. Supported extensions are [png, jpg].',
                'configuration.sections.general.options.rounding.previews.unchecked. The preview file'
                . ' bundles/testthemeconfiguration2/images/previews/square-controls.jpeg does not exist'
                . ' or the extension is not supported. Supported extensions are [png, jpg].'
            ],
            $result
        );
    }

    public function testThemeConfigurationWrongSupportedType(): void
    {
        $bundle = new TestThemeConfiguration3Bundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle->getName() => get_class($bundle)
            ]);

        $result = $this->configurationValidator->validate();

        self::assertEquals(
            [
                'Cannot parse "Resources/views/layouts/*/theme.yml" configuration.'
                . ' The value "radio2" is not allowed for'
                . ' path "themes.wrong_supported_type.configuration.sections.general.options.button_color.type".'
                . ' Permissible values: "select", "radio", "checkbox"'
            ],
            $result
        );
    }
}
