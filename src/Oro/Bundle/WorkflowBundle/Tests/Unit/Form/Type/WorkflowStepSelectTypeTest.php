<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepSelectType;

class WorkflowStepSelectTypeTest extends FormIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var WorkflowStepSelectType
     */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new WorkflowStepSelectType($this->workflowManager);
    }

    protected function getExtensions()
    {
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEntityManager = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $mockEntityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($this->repository));

        $classMetadata = $this->getMockBuilder('\Doctrine\Common\Persistence\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetadata->expects($this->any())
            ->method('getTypeOfField')
            ->will($this->returnValue('integer'));
        $classMetadata->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep'));

        $mockEntityManager->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetadata));

        $mockRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->setMethods(array('getManagerForClass'))
            ->getMock();

        $mockRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($mockEntityManager));

        $mockEntityType = $this->getMockBuilder('Symfony\Bridge\Doctrine\Form\Type\EntityType')
            ->setMethods(array('getName'))
            ->setConstructorArgs(array($mockRegistry))
            ->getMock();

        $mockEntityType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('entity'));

        return array(
            new PreloadedExtension(
                array($mockEntityType->getName() => $mockEntityType),
                array()
            )
        );
    }

    public function testGetName()
    {
        $this->assertEquals('oro_workflow_step_select', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('entity', $this->type->getParent());
    }

    /**
     * @dataProvider incorrectOptionsDataProvider
     * @expectedException \Exception
     * @expectedExceptionMessage Either "workflow_name" or "workflow_entity_class" must be set
     */
    public function testNormalizersException($options)
    {
        $this->factory->create($this->type, null, $options);
    }

    public function testNormalizersByWorkflowName()
    {
        $options = array('workflow_name' => 'test');
        $workflow = $this->getWorkflowMock();

        $this->workflowManager->expects($this->once())
            ->method('getWorkflow')
            ->with('test')
            ->will($this->returnValue($workflow));

        $this->assertQueryBuilderCalled();

        $this->factory->create($this->type, null, $options);
    }

    public function testNormalizersByEntityClass()
    {
        $options = array('workflow_entity_class' => '\stdClass');
        $workflow = $this->getWorkflowMock();

        $this->workflowManager->expects($this->once())
            ->method('getApplicableWorkflowByEntityClass')
            ->with($options['workflow_entity_class'])
            ->will($this->returnValue($workflow));

        $this->assertQueryBuilderCalled();

        $this->factory->create($this->type, null, $options);
    }

    protected function getWorkflowMock()
    {
        $definition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($definition));

        return $workflow;
    }

    protected function assertQueryBuilderCalled()
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('orderBy')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('where')
            ->with('ws.definition = :workflowDefinition')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('workflowDefinition', $this->isInstanceOf('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition'))
            ->will($this->returnSelf());
        $qb->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue(new ArrayCollection()));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('execute'))
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue(array()));
        $query->expects($this->any())
            ->method('getSQL')
            ->will($this->returnValue('SQL QUERY'));
        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('ws')
            ->will($this->returnValue($qb));
    }

    public function incorrectOptionsDataProvider()
    {
        return array(
            array(
                array()
            ),
            array(
                array(
                    'class' => 'OroWorkflowBundle:WorkflowStep',
                    'property' => 'label'
                )
            ),
        );
    }
}
