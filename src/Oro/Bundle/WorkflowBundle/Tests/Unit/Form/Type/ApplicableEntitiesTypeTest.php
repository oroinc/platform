<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Stub\StubEntity;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicableEntitiesTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var  WorkflowEntityConnector|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConnector;

    /** @var ApplicableEntitiesType */
    protected $type;

    protected function setUp(): void
    {
        $this->entityConnector = $this->getMockBuilder(WorkflowEntityConnector::class)
            ->disableOriginalConstructor()->getMock();

        $this->type = new ApplicableEntitiesType($this->entityConnector);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();
        $inflector = (new InflectorFactory())->build();

        $resolver->setDefaults([
            'choices' => [
                $inflector->tableize(StubEntity::class) => StubEntity::class,
                $inflector->tableize(\stdClass::class) => \stdClass::class
            ]
        ]);

        $this->type->configureOptions($resolver);

        $this->entityConnector->expects($this->at(0))
            ->method('isApplicableEntity')
            ->with(StubEntity::class)
            ->willReturn(true);

        $this->entityConnector->expects($this->at(1))
            ->method('isApplicableEntity')
            ->with(\stdClass::class)
            ->willReturn(false);

        $result = $resolver->resolve([]);

        $this->assertEquals(
            [
                $inflector->tableize(StubEntity::class) => StubEntity::class,
            ],
            $result['choices']
        );
    }
}
