<?php
namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Oro\Bundle\UserBundle\Form\Type\GenderType;
use Oro\Bundle\UserBundle\Model\Gender;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class GenderTypeTest extends FormIntegrationTestCase
{
    /**
     * @var array
     */
    protected $genderChoices = [
        'Male' => Gender::MALE,
        'Female' => Gender::FEMALE,
    ];

    /**
     * @var GenderType
     */
    protected $type;

    protected function setUp()
    {
        $genderProvider = $this->getMockBuilder('Oro\Bundle\UserBundle\Provider\GenderProvider')
            ->disableOriginalConstructor()
            ->setMethods(array('getChoices'))
            ->getMock();
        $genderProvider->expects($this->any())
            ->method('getChoices')
            ->will($this->returnValue($this->genderChoices));

        $this->type = new GenderType($genderProvider);
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->type);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->type
                ],
                []
            ),
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

        $actualChoices = array();
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
