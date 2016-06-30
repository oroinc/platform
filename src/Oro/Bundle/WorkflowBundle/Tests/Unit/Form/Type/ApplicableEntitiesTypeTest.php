<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\WorkflowBundle\Form\Type\ApplicableEntitiesType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowEntityConnector;
use Oro\Bundle\WorkflowBundle\Tests\Unit\Fixtures\StubEntity;

class ApplicableEntitiesTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  WorkflowEntityConnector|\PHPUnit_Framework_MockObject_MockObject */
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
                StubEntity::class => Inflector::tableize(StubEntity::class),
                \stdClass::class => Inflector::tableize(\stdClass::class)
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
                StubEntity::class => Inflector::tableize(StubEntity::class)
            ],
            $result['choices']
        );
    }
}
