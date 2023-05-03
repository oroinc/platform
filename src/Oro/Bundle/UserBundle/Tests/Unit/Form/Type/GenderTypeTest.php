<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Form\Type;

use Oro\Bundle\UserBundle\Form\Type\GenderType;
use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Bundle\UserBundle\Provider\GenderProvider;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class GenderTypeTest extends FormIntegrationTestCase
{
    private array $genderChoices = [
        'Male' => Gender::MALE,
        'Female' => Gender::FEMALE,
    ];

    private GenderType $type;

    protected function setUp(): void
    {
        $genderProvider = $this->createMock(GenderProvider::class);
        $genderProvider->expects($this->any())
            ->method('getChoices')
            ->willReturn($this->genderChoices);

        $this->type = new GenderType($genderProvider);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    public function testBindValidData()
    {
        $form = $this->factory->create(GenderType::class);

        $form->submit(Gender::MALE);
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(Gender::MALE, $form->getData());

        $view = $form->createView();
        $this->assertFalse($view->vars['multiple']);
        $this->assertFalse($view->vars['expanded']);
        $this->assertNotEmpty($view->vars['placeholder']);
        $this->assertNotEmpty($view->vars['choices']);

        $actualChoices = [];
        /** @var ChoiceView $choiceView */
        foreach ($view->vars['choices'] as $choiceView) {
            $actualChoices[$choiceView->value] = $choiceView->label;
        }
        $this->assertEquals(array_flip($this->genderChoices), $actualChoices);
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }
}
