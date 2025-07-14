<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationParentSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationParentSelectTypeTest extends TestCase
{
    use EntityTrait;

    private LocalizationParentSelectType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->formType = new LocalizationParentSelectType();
    }

    public function testGetParent(): void
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testGetName(): void
    {
        $this->assertEquals(LocalizationParentSelectType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions(): void
    {
        $optionsResolver = $this->createMock(OptionsResolver::class);
        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(function (array $options) use ($optionsResolver) {
                $this->assertArrayHasKey('autocomplete_alias', $options);
                $this->assertEquals('oro_localization_parent', $options['autocomplete_alias']);

                $this->assertArrayHasKey('configs', $options);
                $this->assertEquals(
                    [
                        'component' => 'autocomplete-entity-parent',
                        'placeholder' => 'oro.locale.localization.form.placeholder.select_parent_localization'
                    ],
                    $options['configs']
                );

                return $optionsResolver;
            });

        $this->formType->configureOptions($optionsResolver);
    }

    /**
     * @dataProvider buildViewDataProvider
     */
    public function testBuildView(?object $parentData, ?int $expectedParentId, array $expectedIds): void
    {
        $parentForm = $this->createMock(FormInterface::class);
        $parentForm->expects($this->once())
            ->method('getData')
            ->willReturn($parentData);

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        $formView = new FormView();

        $this->formType->buildView($formView, $form, []);

        $this->assertArrayHasKey('configs', $formView->vars);
        $this->assertArrayHasKey('entityId', $formView->vars['configs']);
        $this->assertEquals($expectedParentId, $formView->vars['configs']['entityId']);

        $this->assertArrayHasKey('grid_parameters', $formView->vars);
        $this->assertIsArray($formView->vars['grid_parameters']);
        $this->assertArrayHasKey('ids', $formView->vars['grid_parameters']);
        $this->assertIsArray($formView->vars['grid_parameters']['ids']);
        $this->assertEquals($expectedIds, $formView->vars['grid_parameters']['ids']);
    }

    public function buildViewDataProvider(): array
    {
        return [
            'without entity' => [
                'parentData' => null,
                'expectedParentId' => null,
                'expectedIds' => [],
            ],
            'with entity' => [
                'parentData' => $this->getEntity(
                    Localization::class,
                    [
                        'id' => 42,
                        'childLocalizations' => [
                            $this->getEntity(
                                Localization::class,
                                [
                                    'id' => 105,
                                    'childLocalizations' => [
                                        $this->getEntity(Localization::class, ['id' => 110])
                                    ]
                                ]
                            ),
                            $this->getEntity(Localization::class, ['id' => 120])
                        ]
                    ]
                ),
                'expectedParentId' => 42,
                'expectedIds' => [42, 105, 110, 120],
            ],
        ];
    }
}
