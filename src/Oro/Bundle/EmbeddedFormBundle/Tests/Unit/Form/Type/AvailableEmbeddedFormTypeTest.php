<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AvailableEmbeddedFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithManager()
    {
        new AvailableEmbeddedFormType($this->createEmbeddedFormManagerMock());
    }

    /**
     * @test
     */
    public function shouldSetDefaultOptions()
    {
        $availableForms = ['myForm' => 'Label'];
        $manager = $this->createEmbeddedFormManagerMock();
        $manager->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($availableForms));

        $resolver = $this->getMock('\Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['choices' => $availableForms]);

        $formType = new AvailableEmbeddedFormType($manager);
        $formType->setDefaultOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldReturnFormName()
    {
        $formType = new AvailableEmbeddedFormType($this->createEmbeddedFormManagerMock());

        $this->assertEquals('oro_available_embedded_forms', $formType->getName());
    }

    /**
     * @test
     */
    public function shouldReturnChoiceAsParent()
    {
        $formType = new AvailableEmbeddedFormType($this->createEmbeddedFormManagerMock());

        $this->assertEquals('choice', $formType->getParent());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEmbeddedFormManagerMock()
    {
        return $this
            ->getMockBuilder(
                'Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager'
            )
            ->disableOriginalConstructor()
            ->getMock();
    }
}
