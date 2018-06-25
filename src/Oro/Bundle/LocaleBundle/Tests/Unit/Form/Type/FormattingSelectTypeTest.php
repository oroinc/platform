<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FormattingSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LocalizationChoicesProvider
     */
    protected $provider;

    /**
     * @var AbstractType
     */
    protected $formType;

    public function setUp()
    {
        $this->provider = $this->createMock(LocalizationChoicesProvider::class);
        $this->formType = new FormattingSelectType($this->provider);
        parent::setUp();
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(FormattingSelectType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $data =  [
            'English' => 'en',
            'Spain' => 'es',
        ];

        $this->provider->expects($this->once())->method('getFormattingChoices')->willReturn($data);

        $form = $this->factory->create(FormattingSelectType::class);

        $choices = $form->createView()->vars['choices'];

        $this->assertEquals(
            [
                new ChoiceView('en', 'en', 'English'),
                new ChoiceView('es', 'es', 'Spain')
            ],
            $choices
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $choiceType = $this->getMockBuilder(OroChoiceType::class)
            ->setMethods(['configureOptions', 'getParent'])
            ->disableOriginalConstructor()
            ->getMock();
        $choiceType->expects($this->any())->method('getParent')->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    FormattingSelectType::class => $this->formType,
                    OroChoiceType::class => $choiceType
                ],
                []
            )
        ];
    }
}
