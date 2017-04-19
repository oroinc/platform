<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class FormattingSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LocalizationChoicesProvider
     */
    protected $provider;

    /**
     * @var AbstractType
     */
    protected $formType;

    public function setUp()
    {
        parent::setUp();

        $this->provider = $this->createMock(LocalizationChoicesProvider::class);
        $this->formType = new FormattingSelectType($this->provider);
    }

    public function testGetParent()
    {
        $this->assertEquals(OroChoiceType::NAME, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals(FormattingSelectType::NAME, $this->formType->getName());
    }

    public function testBuildForm()
    {
        $data =  [
            'en' => 'English',
            'es' => 'Spain'
        ];

        $this->provider->expects($this->once())->method('getFormattingChoices')->willReturn($data);

        $form = $this->factory->create($this->formType);

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
        $choiceType->expects($this->any())->method('getParent')->willReturn('choice');

        return [
            new PreloadedExtension(['oro_choice' => $choiceType], [])
        ];
    }
}
