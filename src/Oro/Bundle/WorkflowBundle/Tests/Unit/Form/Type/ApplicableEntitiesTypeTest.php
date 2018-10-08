<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Inflector\Inflector;
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

    protected function setUp()
    {
        $this->entityConnector = $this->getMockBuilder(WorkflowEntityConnector::class)
            ->disableOriginalConstructor()->getMock();

        $this->type = new ApplicableEntitiesType($this->entityConnector);
    }

    public function testConfigureOptions()
    {
        $resolver = new OptionsResolver();

        $resolver->setDefaults([
            'choices' => [
                Inflector::tableize(StubEntity::class) => StubEntity::class,
                Inflector::tableize(\stdClass::class) => \stdClass::class
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
                Inflector::tableize(StubEntity::class) => StubEntity::class,
            ],
            $result['choices']
        );
    }
}
