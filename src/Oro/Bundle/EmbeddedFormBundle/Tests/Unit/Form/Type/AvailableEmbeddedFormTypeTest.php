<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\AvailableEmbeddedFormType;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AvailableEmbeddedFormTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmbeddedFormManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var AvailableEmbeddedFormType */
    private $formType;

    protected function setUp(): void
    {
        $this->manager = $this->createMock(EmbeddedFormManager::class);

        $this->formType = new AvailableEmbeddedFormType($this->manager);
    }

    public function testShouldConfigureOptions()
    {
        $availableForms = ['myForm' => 'Label'];
        $this->manager->expects($this->once())
            ->method('getAll')
            ->willReturn($availableForms);

        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['choices' => array_flip($availableForms),]);

        $this->formType->configureOptions($resolver);
    }

    public function testShouldReturnFormName()
    {
        $this->assertEquals('oro_available_embedded_forms', $this->formType->getName());
    }

    public function testShouldReturnChoiceAsParent()
    {
        $this->assertEquals(ChoiceType::class, $this->formType->getParent());
    }
}
