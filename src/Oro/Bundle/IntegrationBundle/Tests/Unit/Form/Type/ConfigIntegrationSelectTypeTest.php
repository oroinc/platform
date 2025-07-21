<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\DataTransformer\IdToEntityTransformer;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Form\Type\ConfigIntegrationSelectType;
use Oro\Bundle\IntegrationBundle\Form\Type\IntegrationSelectType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigIntegrationSelectTypeTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private ConfigIntegrationSelectType $formType;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->formType = new ConfigIntegrationSelectType($this->registry);
    }

    public function testBuildForm(): void
    {
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
