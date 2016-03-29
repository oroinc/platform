<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\Sorter;

class PostgresqlGridModifierTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityClassResolver;

    protected function setUp()
    {
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityClassResolver = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\EntityClassResolver')
            ->setMethods(['getEntityClass'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testAddingIdentifierColumnToSorting()
    {
        $className = 'Oro\Bundle\UserBundle\Entity\User';
        $alias = 'us';
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->setMethods(['getEntityManager', 'getClassMetadata', 'getDQLPart', 'addOrderBy'])
            ->disableOriginalConstructor()
            ->getMock();
        $datagridConfig = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $datasource = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface')
            ->setMethods(['getQueryBuilder', 'process', 'getResults'])
            ->disableOriginalConstructor()
            ->getMock();
        $modifier = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\Sorter\PostgresqlGridModifier')
            ->setMethods(['getEntity'])
            ->setConstructorArgs([$this->container, $this->entityClassResolver])
            ->getMock();
        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $from = $this->getMockBuilder('Doctrine\ORM\Query\expr\From')
            ->setMethods(['getFrom', 'getAlias'])
            ->disableOriginalConstructor()
            ->getMock();
        $modifier->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($className));
        $datasource->expects($this->once())
            ->method('getQueryBuilder')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->once())
            ->method('getEntityManager')
            ->will($this->returnValue($queryBuilder));
        $queryBuilder->expects($this->once())
            ->method('getClassMetadata')
            ->with($className)
            ->will($this->returnValue($classMetadata));
        $classMetadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));
        $queryBuilder->expects($this->exactly(2))
            ->method('getDQLPart')
            ->will($this->returnValue([$from]));
        $from->expects($this->once())
            ->method('getFrom')
            ->will($this->returnValue($className));
        $from->expects($this->once())
            ->method('getAlias')
            ->will($this->returnValue($alias));
        $this->entityClassResolver->expects($this->once())
            ->method('getEntityClass')
            ->with($className)
            ->will($this->returnValue($className));
        $queryBuilder->expects($this->once())
            ->method('addOrderBy')
            ->with($alias.'.'.'id', 'ASC');

        $modifier->visitDatasource($datagridConfig, $datasource);
    }
}
