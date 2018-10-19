<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class AvailableEmbeddedFormTypeTest extends \PHPUnit\Framework\TestCase
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
    public function shouldConfigureOptions()
    {
        $availableForms = ['myForm' => 'Label'];
        $manager = $this->createEmbeddedFormManagerMock();
        $manager->expects($this->once())
            ->method('getAll')
            ->will($this->returnValue($availableForms));

        $resolver = $this->createMock('\Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'choices' => array_flip($availableForms),
            ]);

        $formType = new AvailableEmbeddedFormType($manager);
        $formType->configureOptions($resolver);
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

        $this->assertEquals(ChoiceType::class, $formType->getParent());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
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
