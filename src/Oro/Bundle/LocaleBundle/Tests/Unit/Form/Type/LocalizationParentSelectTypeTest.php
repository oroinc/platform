<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizationParentSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizationParentSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationParentSelectType */
    protected $formType;

    public function setUp()
    {
        $this->formType = new LocalizationParentSelectType();
    }

    public function tearDown()
    {
        unset($this->formType);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroEntitySelectOrCreateInlineType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(LocalizationParentSelectType::NAME, $this->formType->getName());
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $optionsResolver */
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $optionsResolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'))
            ->willReturnCallback(
                function (array $options) {
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
                }
            );

        $this->formType->configureOptions($optionsResolver);
    }


    /**
     * @dataProvider buildViewDataProvider
     *
     * @param object|null $parentData
     * @param int|null $expectedParentId
     * @param array $expectedIds
     */
    public function testBuildView($parentData, $expectedParentId, array $expectedIds)
    {
        $parentForm = $this->createMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->once())->method('getData')->willReturn($parentData);

        $form = $this->createMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())->method('getParent')->willReturn($parentForm);

        $formView = new FormView();

        $this->formType->buildView($formView, $form, []);

        $this->assertArrayHasKey('configs', $formView->vars);
        $this->assertArrayHasKey('entityId', $formView->vars['configs']);
        $this->assertEquals($expectedParentId, $formView->vars['configs']['entityId']);

        $this->assertArrayHasKey('grid_parameters', $formView->vars);
        $this->assertInternalType('array', $formView->vars['grid_parameters']);
        $this->assertArrayHasKey('ids', $formView->vars['grid_parameters']);
        $this->assertInternalType('array', $formView->vars['grid_parameters']['ids']);
        $this->assertEquals($expectedIds, $formView->vars['grid_parameters']['ids']);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
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
