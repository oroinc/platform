<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NavigationBundle\Form\Type\MenuChoiceType;
use Oro\Bundle\NavigationBundle\Provider\MenuNamesProvider;
use Oro\Bundle\TranslationBundle\Form\Extension\TranslatableChoiceTypeExtension;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MenuChoiceTypeTest extends FormIntegrationTestCase
{
    private const SCOPE_TYPE = 'sample_scope';
    private const MENU_NAMES = ['menu1', 'menu2'];

    private MenuChoiceType $menuChoiceType;

    protected function getExtensions(): array
    {
        $menuNamesProvider = $this->createMock(MenuNamesProvider::class);
        $menuNamesProvider->expects(self::any())
            ->method('getMenuNames')
            ->willReturnMap([
                [self::SCOPE_TYPE, self::MENU_NAMES],
                ['', self::MENU_NAMES],
            ]);

        $this->menuChoiceType = new MenuChoiceType($menuNamesProvider);

        return [
            new PreloadedExtension(
                [
                    MenuChoiceType::class => $this->menuChoiceType,
                ],
                [
                    ChoiceType::class => [
                        new TranslatableChoiceTypeExtension(),
                    ],
                ]
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @dataProvider configureOptionsDataProvider
     */
    public function testConfigureOptions(array $options, array $expected): void
    {
        $optionsResolver = new OptionsResolver();
        $this->menuChoiceType->configureOptions($optionsResolver);

        self::assertEquals($expected, $optionsResolver->resolve($options));
    }

    public function configureOptionsDataProvider(): array
    {
        return [
            'empty' => [
                'options' => ['scope_type' => ''],
                'expected' => [
                    'scope_type' => '',
                    'choices' => array_combine(self::MENU_NAMES, self::MENU_NAMES),
                    'translatable_options' => false,
                    'multiple' => false,
                ]
            ],
            'with choices' => [
                'options' => ['scope_type' => self::SCOPE_TYPE, 'choices' => ['label' => 'key']],
                'expected' => [
                    'scope_type' => self::SCOPE_TYPE,
                    'choices' => ['label' => 'key'],
                    'translatable_options' => false,
                    'multiple' => false,
                ]
            ],
        ];
    }

    public function testBuildForm(): void
    {
        $form = $this->factory->create(
            MenuChoiceType::class,
            null,
            ['scope_type' => self::SCOPE_TYPE]
        );

        $this->assertFormOptionEqual(array_combine(self::MENU_NAMES, self::MENU_NAMES), 'choices', $form);
    }

    public function testSubmit(): void
    {
        $form = $this->factory->create(
            MenuChoiceType::class,
            null,
            ['scope_type' => self::SCOPE_TYPE]
        );

        $form->submit('menu1');

        $this->assertFormIsValid($form);

        self::assertEquals('menu1', $form->getData());
    }

    public function testSubmitInvalidOption(): void
    {
        $form = $this->factory->create(
            MenuChoiceType::class,
            null,
            ['scope_type' => self::SCOPE_TYPE]
        );

        $form->submit('invalid');

        $this->assertFormIsNotValid($form);
        self::assertNull($form->getData());
    }
}
