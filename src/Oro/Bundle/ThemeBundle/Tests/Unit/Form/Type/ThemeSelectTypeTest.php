<?php

declare(strict_types=1);

namespace Oro\Bundle\ThemeBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ThemeBundle\Form\Type\ThemeSelectType;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ThemeSelectTypeTest extends TestCase
{
    private ThemeManager&MockObject $themeManager;
    private ThemeSelectType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->type = new ThemeSelectType($this->themeManager);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }

    public function testConfigureOptionsWithoutGroup(): void
    {
        $themes = [
            $this->getTheme('theme1', 'label1', 'icon1', 'logo1', 'screenshot1', 'description1'),
            $this->getTheme('theme2', 'label2', 'icon2', 'logo2', 'screenshot2', 'description2')
        ];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with(null)
            ->willReturn($themes);

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $options = $resolver->resolve();

        self::assertArrayHasKey('theme_group', $options);
        self::assertNull($options['theme_group']);
        self::assertArrayHasKey('choices', $options);
    }

    public function testConfigureOptionsWithCommerceGroup(): void
    {
        $themes = [
            $this->getTheme('theme1', 'label1', 'icon1', 'logo1', 'screenshot1', 'description1'),
            $this->getTheme('theme2', 'label2', 'icon2', 'logo2', 'screenshot2', 'description2')
        ];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $resolver = new OptionsResolver();
        $this->type->configureOptions($resolver);

        $options = $resolver->resolve(['theme_group' => 'commerce']);

        self::assertEquals('commerce', $options['theme_group']);
        self::assertArrayHasKey('choices', $options);
    }

    public function testFinishViewWithGroup(): void
    {
        $themes = [
            $this->getTheme('theme1', 'label1', 'icon1', 'logo1', 'screenshot1', 'description1'),
            $this->getTheme('theme2', 'label2', 'icon2', 'logo2', 'screenshot2', 'description2')
        ];

        $this->themeManager->expects(self::once())
            ->method('getEnabledThemes')
            ->with('commerce')
            ->willReturn($themes);

        $view = new FormView();
        $form = $this->createMock(FormInterface::class);
        $options = [
            'theme_group' => 'commerce',
            'choices' => ['label1' => 'theme1', 'label2' => 'theme2']  // Choices format: label => value
        ];

        $this->type->finishView($view, $form, $options);

        $expectedMetadata = [
            'theme1' => [
                'icon' => 'icon1',
                'logo' => 'logo1',
                'screenshot' => 'screenshot1',
                'description' => 'description1'
            ],
            'theme2' => [
                'icon' => 'icon2',
                'logo' => 'logo2',
                'screenshot' => 'screenshot2',
                'description' => 'description2'
            ]
        ];

        self::assertArrayHasKey('themes-metadata', $view->vars);
        self::assertEquals($expectedMetadata, $view->vars['themes-metadata']);
    }

    private function getTheme(
        string $name,
        string $label,
        string $icon,
        string $logo,
        string $screenshot,
        string $description
    ): Theme {
        $theme = new Theme($name);
        $theme->setLabel($label);
        $theme->setIcon($icon);
        $theme->setLogo($logo);
        $theme->setScreenshot($screenshot);
        $theme->setDescription($description);

        return $theme;
    }
}
