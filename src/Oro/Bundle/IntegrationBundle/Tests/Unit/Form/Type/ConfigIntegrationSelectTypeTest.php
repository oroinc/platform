<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\IdToEntityTransformer;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ConfigIntegrationSelectType;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationSelectType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigIntegrationSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var ConfigIntegrationSelectType */
    private $formType;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new ConfigIntegrationSelectType($this->registry);

        parent::setUp();
    }

    public function testBuildForm(): void
    {
        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('addModelTransformer')
            ->with(new IdToEntityTransformer($this->registry, Channel::class));

        $this->formType->buildForm($builder, []);
    }

    public function testGetParent(): void
    {
        $this->assertEquals(IntegrationSelectType::class, $this->formType->getParent());
    }
}
