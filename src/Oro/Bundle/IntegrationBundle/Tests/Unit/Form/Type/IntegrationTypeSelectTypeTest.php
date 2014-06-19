<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationTypeSelectType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class IntegrationTypeSelectTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  IntegrationTypeSelectType */
    protected $type;

    /** @var TypesRegistry */
    protected $registry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->registry   = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()->getMock();
        $this->type       = new IntegrationTypeSelectType($this->registry, $this->translator);
    }

    public function testSetDefaultOptions()
    {
        /** @var OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->setDefaultOptions($resolver);
    }

    public function testBuildView()
    {
        $view       = $this->getMockBuilder('Symfony\Component\Form\FormView')->disableOriginalConstructor()->getMock();
        $form       = $this->getMockBuilder('Symfony\Component\Form\Form')->disableOriginalConstructor()->getMock();
        $options    = ['configs' => []];
        $choiceList = $this->getMockBuilder('Symfony\Component\Form\Extension\Core\ChoiceList\SimpleChoiceList')
            ->disableOriginalConstructor()->getMock();
        $choiceView = $this->getMockBuilder('Symfony\Component\Form\Extension\Core\View\ChoiceView')
            ->disableOriginalConstructor()->getMock();
        $choiceList->expects($this->once())
            ->method('getRemainingViews')
            ->will($this->returnValue([$choiceView]));
        $options['choice_list'] = $choiceList;
        $this->type->buildView($view, $form, $options);
    }

    public function testGetParent()
    {
        $this->assertEquals('genemu_jqueryselect2_choice', $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_integration_type_select', $this->type->getName());
    }

}
