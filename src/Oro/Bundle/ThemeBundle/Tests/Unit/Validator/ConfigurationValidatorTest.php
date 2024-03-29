<?php

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Validator;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\ThemeBundle\Form\Provider\ConfigurationBuildersProvider;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle\TestThemeConfigurationBundle;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle2\TestThemeConfigurationBundle2;
use Oro\Bundle\ThemeBundle\Tests\Unit\Stubs\Bundles\TestThemeConfigurationBundle3\TestThemeConfigurationBundle3;
use Oro\Bundle\ThemeBundle\Validator\ChainConfigurationValidator;
use Oro\Bundle\ThemeBundle\Validator\DefinitionConfigurationValidator;
use Oro\Bundle\ThemeBundle\Validator\PreviewConfigurationValidator;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

final class ConfigurationValidatorTest extends TestCase
{
    use TempDirExtension;

    private ChainConfigurationValidator $configurationValidator;

    private ConfigurationBuildersProvider $configurationBuildersProvider;

    protected function setUp(): void
    {
        $this->configurationBuildersProvider = $this->createStub(ConfigurationBuildersProvider::class);
        $this->configurationBuildersProvider
            ->method('getConfigurationTypes')
            ->willReturn(['select', 'radio', 'checkbox']);

        $themeConfiguration = new ThemeConfiguration($this->configurationBuildersProvider);
        $provider = new ThemeConfigurationProvider(
            $this->getTempFile('ConfigurationProvider'),
            false,
            $themeConfiguration,
            '[a-zA-Z][a-zA-Z0-9_\-:]*'
        );

        $validatorIterator = new \ArrayIterator(
            [
                (new PreviewConfigurationValidator()),
                (new DefinitionConfigurationValidator($themeConfiguration))
            ]
        );

        $this->configurationValidator = new ChainConfigurationValidator(
            $provider,
            $validatorIterator
        );
    }

    public function testThemeConfigurationShouldBeValid(): void
    {
        $bundle1 = new TestThemeConfigurationBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle1->getName() => get_class($bundle1)
                ]
            );

        $messages = $this->configurationValidator->validate();

        self::assertEmpty($messages);
    }

    public function testThemeConfigurationImagesAreMissOrHasWrongExtension(): void
    {
        $bundle2 = new TestThemeConfigurationBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle2->getName() => get_class($bundle2)
                ]
            );

        $result = $this->configurationValidator->validate();

        self::assertCount(5, $result);
        self::assertEquals(
            [
                sprintf(
                    'configuration.sections.general.palette.modern in %s. ' .
                    'The preview file bundles/orofrontend/images/previews/modern-palette.png does not exist, ' .
                    'or the extension is not supported, supported extensions are [png, jpg]',
                    TestThemeConfigurationBundle2::class
                ),
                sprintf(
                    'configuration.sections.general.palette.traditional in %s. ' .
                    'The preview file bundles/orofrontend/images/previews/tradition-pallete.jpg does not exist, ' .
                    'or the extension is not supported, supported extensions are [png, jpg]',
                    TestThemeConfigurationBundle2::class
                ),
                sprintf(
                    'configuration.sections.general.rounding.checked in %s. ' .
                    'The preview file bundles/orofrontend/images/previews/rounded-controls.png does not exist, ' .
                    'or the extension is not supported, supported extensions are [png, jpg]',
                    TestThemeConfigurationBundle2::class
                ),
                sprintf(
                    'configuration.sections.general.rounding.unchecked in %s. ' .
                    'The preview file bundles/orofrontend/images/previews/square-controls.jpeg does not exist, ' .
                    'or the extension is not supported, supported extensions are [png, jpg]',
                    TestThemeConfigurationBundle2::class
                ),
                sprintf(
                    <<<END
Cannot parse "%s" configuration. The path "%s" cannot contain an empty value, but got "".
Hint: The option label is displayed in the theme configuration UI.
END,
                    'Resources/views/layouts/*/theme.yml',
                    'themes.images-miss-config.configuration.sections.general.options.palette.label'
                )
            ],
            $result
        );
    }

    public function testThemeConfigurationWrongSupportedType(): void
    {
        $bundle3 = new TestThemeConfigurationBundle3();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(
                [
                    $bundle3->getName() => get_class($bundle3)
                ]
            );

        $result = $this->configurationValidator->validate();

        self::assertCount(1, $result);

        self::assertEquals(
            [
                sprintf(
                    'Cannot parse "%s" configuration. The value "radio2" is not allowed for path "%s". ' .
                    'Permissible values: "select", "radio", "checkbox"',
                    'Resources/views/layouts/*/theme.yml',
                    'themes.wrong_supported_type.configuration.sections.general.options.button_color.type'
                )
            ],
            $result
        );
    }
}
