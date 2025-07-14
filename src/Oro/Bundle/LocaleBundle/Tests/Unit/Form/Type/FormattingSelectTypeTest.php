<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroChoiceType;
use Oro\Bundle\LocaleBundle\Form\Type\FormattingSelectType;
use Oro\Bundle\LocaleBundle\Provider\LocalizationChoicesProvider;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FormattingSelectTypeTest extends FormIntegrationTestCase
{
    private LocalizationChoicesProvider&MockObject $provider;
    private AbstractType $formType;

    #[\Override]
    protected function setUp(): void
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

        $this->provider->expects($this->once())
            ->method('getFormattingChoices')
            ->willReturn($data);

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

    #[\Override]
    protected function getExtensions(): array
    {
        $choiceType = $this->createMock(OroChoiceType::class);
        $choiceType->expects($this->any())
            ->method('getParent')
            ->willReturn(ChoiceType::class);

        return [
            new PreloadedExtension(
                [
                    $this->formType,
                    $choiceType
                ],
                []
            )
        ];
    }
}
