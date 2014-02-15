<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\RemoveEntitiesStep;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class RemoveEntitiesStepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RemoveEntitiesStep
     */
    protected $step;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->constraintViolation = $this
            ->getMockBuilder('Symfony\Component\Validator\ConstraintViolationList')
            ->disableOriginalConstructor()
            ->getMock();

        $this->step = new RemoveEntitiesStep($this->entityManager, $this->doctrineHelper);
    }

    public function testRun()
    {
        $data = $this->createEntityData();

        $foo = new EntityStub(1);
        $bar = new EntityStub(2);
        $baz = new EntityStub(3);

        $entities = array($foo, $bar, $baz);

        $data->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($foo));

        $data->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue($entities));

        $this->doctrineHelper->expects($this->at(0))
            ->method('isEntityEqual')
            ->with($foo, $foo)
            ->will($this->returnValue(true));

        $this->doctrineHelper->expects($this->at(1))
            ->method('isEntityEqual')
            ->with($foo, $bar)
            ->will($this->returnValue(false));

        $this->doctrineHelper->expects($this->at(2))
            ->method('isEntityEqual')
            ->with($foo, $baz)
            ->will($this->returnValue(false));

        $this->entityManager->expects($this->at(0))
            ->method('remove')
            ->with($bar);

        $this->entityManager->expects($this->at(1))
            ->method('remove')
            ->with($baz);

        $this->step->run($data);
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
