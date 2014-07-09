<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\ImportExport\Reader;

use Doctrine\ORM\Query;

use Oro\Bundle\IntegrationBundle\Reader\EntityReaderById;

class EntityReaderByIdTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextRegistry;

    /**
     * @var EntityReaderById
     */
    protected $reader;

    public function setUp()
    {
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->setMethods(array('getByStepExecution'))
            ->getMock();

        $this->managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $this->reader = new EntityReaderById($this->contextRegistry, $this->managerRegistry);
    }

    public function testSetStepExecutionWithEntityName()
    {
        $entityName = 'entityName';

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()
            ->getMock();

        $classMetadata->expects($this->once())->method('getAssociationMappings')->will($this->returnValue(array()));
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entityManager->expects($this->once())->method('getClassMetadata')
            ->with($entityName)
            ->will($this->returnValue($classMetadata));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')->disableOriginalConstructor()->getMock();

        $queryBuilder->expects($this->once())->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')->disableOriginalConstructor()->getMock();
        $repository->expects($this->once())->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($queryBuilder));

        $this->managerRegistry->expects($this->once())->method('getRepository')
            ->with($entityName)
            ->will($this->returnValue($repository));

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->at(0))->method('hasOption')->with('entityName')->will($this->returnValue(true));
        $context->expects($this->at(1))->method('getOption')
            ->with('entityName')
            ->will($this->returnValue($entityName));

        $this->reader->setStepExecution($this->getMockStepExecution($context));

        $this->assertAttributeInstanceOf(
            'Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator',
            'sourceIterator',
            $this->reader
        );

        $this->assertAttributeEquals(
            $queryBuilder,
            'source',
            self::readAttribute($this->reader, 'sourceIterator')
        );
    }

    /**
     * @expectedException \Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Configuration of entity reader must contain either "entityName".
     */
    public function testSetStepExecutionFailsWhenHasNoRequiredOptions()
    {
        $this->managerRegistry->expects($this->never())->method($this->anything());

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')->getMock();
        $context->expects($this->exactly(1))
            ->method('hasOption')
            ->will($this->returnValue(false));

        $this->reader->setStepExecution($this->getMockStepExecution($context));
    }

    public function testSetSourceEntityName()
    {
        $name = '\stdClass';
        $id = 1;

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->getMock();

        $context->expects($this->exactly(1))
            ->method('hasOption')
            ->will($this->returnValue($name));

        $context->expects($this->at(0))
            ->method('getOption')
            ->will($this->returnValue($name));

        $context->expects($this->at(1))
            ->method('getOption')
            ->will($this->returnValue($id));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getAssociationMappings')
            ->will(
                $this->returnValue(
                    array(
                        array('fieldName' => 'test')
                    )
                )
            );
        $classMetadata->expects($this->once())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(['id']));

        $queryBuilder->expects($this->once())
            ->method('addSelect')
            ->with('_test');
        $queryBuilder->expects($this->once())
            ->method('leftJoin')
            ->with('o.test', '_test');

        $queryBuilder->expects($this->once())
            ->method('orderBy')
            ->with('o.id', 'ASC');

        $queryBuilder->expects($this->once())
            ->method('add')
            ->with('where', 'o.id = :id');

        $queryBuilder->expects($this->any())
            ->method('setParameter')
            ->with('id', null, null);

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($queryBuilder));

        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with($name)
            ->will($this->returnValue($repository));

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        $entityManager->expects($this->once())
            ->method('getClassMetadata')
            ->with($name)
            ->will($this->returnValue($classMetadata));

        $queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($entityManager));

        $this->reader->setSourceEntityName($context);
    }

    /**
     * @param mixed $context
     * @return \PHPUnit_Framework_MockObject_MockObject+
     */
    protected function getMockStepExecution($context)
    {
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $this->contextRegistry->expects($this->any())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        return $stepExecution;
    }
}
