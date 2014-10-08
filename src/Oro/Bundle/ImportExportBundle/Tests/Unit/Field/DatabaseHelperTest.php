<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;

class DatabaseHelperTest extends \PHPUnit_Framework_TestCase
{
    const TEST_CLASS = 'stdClass';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $doctrineHelper;

    /**
     * @var DatabaseHelper
     */
    protected $helper;

    protected function setUp()
    {
        $this->metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects($this->any())
            ->method('getClassMetadata')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->metadata));

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->entityManager));
        $registry->expects($this->any())
            ->method('getRepository')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->repository));

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new DatabaseHelper($registry, $this->doctrineHelper);
    }

    public function testFindOneBy()
    {
        $entity = new \stdClass();
        $entity->id = 1;
        $relatedEntity = new \stdClass();
        $relatedEntity->id = 2;
        $criteria = ['id' => 1, 'related' => $relatedEntity];

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->with($relatedEntity)
            ->will($this->returnValue($relatedEntity->id));

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getOneOrNullResult'))
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->will($this->returnValue($entity));

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('e.id = :id AND e.related = :related')
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('setParameters')
            ->with($criteria)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->will($this->returnSelf());
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->with('e')
            ->will($this->returnValue($queryBuilder));

        // findOneBy executed two times to check internal cache
        $this->assertEquals($entity, $this->helper->findOneBy(self::TEST_CLASS, $criteria));
        $this->assertEquals($entity, $this->helper->findOneBy(self::TEST_CLASS, $criteria));

        // test clearing of internal cache
        $this->assertAttributeNotEmpty('entities', $this->helper);
        $this->helper->onClear();
        $this->assertAttributeEmpty('entities', $this->helper);
    }

    public function testFind()
    {
        $entity = new \stdClass();
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->will($this->returnValue($entity));

        $this->assertEquals($entity, $this->helper->find(self::TEST_CLASS, $identifier));
    }

    public function testGetIdentifier()
    {
        $entity = new \stdClass();
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($identifier));

        $this->assertEquals($identifier, $this->helper->getIdentifier($entity));
    }

    public function testGetIdentifierFieldName()
    {
        $fieldName = 'id';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($fieldName));

        $this->assertEquals($fieldName, $this->helper->getIdentifierFieldName(self::TEST_CLASS));
    }

    /**
     * @param array $mapping
     * @param bool $isCascade
     * @dataProvider isCascadePersistDataProvider
     */
    public function testIsCascadePersist(array $mapping, $isCascade)
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->will($this->returnValue($mapping));

        $this->assertEquals($isCascade, $this->helper->isCascadePersist(self::TEST_CLASS, $fieldName));
    }

    /**
     * @return array
     */
    public function isCascadePersistDataProvider()
    {
        return array(
            'no cascade operations' => [
                'mapping'   => [],
                'isCascade' => false,
            ],
            'no cascade persist' => [
                'mapping'   => ['cascade' => ['remove']],
                'isCascade' => false,
            ],
            'cascade persist' => [
                'mapping'   => ['cascade' => ['persist']],
                'isCascade' => true,
            ],
        );
    }

    public function testResetIdentifier()
    {
        $entity = new \stdClass();
        $fieldName = 'id';

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($fieldName));

        $this->metadata->expects($this->once())
            ->method('setIdentifierValues')
            ->with($entity, [$fieldName => null])
            ->will($this->returnValue($fieldName));

        $this->helper->resetIdentifier($entity);
    }

    /**
     * @param array $association
     * @param string $expectedField
     * @dataProvider getInversedRelationFieldNameDataProvider
     */
    public function testGetInversedRelationFieldName(array $association, $expectedField)
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->will($this->returnValue($association));

        $this->assertEquals($expectedField, $this->helper->getInversedRelationFieldName(self::TEST_CLASS, $fieldName));
    }

    /**
     * @return array
     */
    public function getInversedRelationFieldNameDataProvider()
    {
        return array(
            'mapped by field' => array(
                'association' => array('mappedBy' => 'field'),
                'expectedField' => 'field',
            ),
            'inversed by field' => array(
                'association' => array('inversedBy' => 'field'),
                'expectedField' => 'field',
            ),
            'no inversed field' => array(
                'association' => array(),
                'expectedField' => null,
            ),
        );
    }

    /**
     * @param string $type
     * @param bool $expected
     * @dataProvider isSingleInversedRelationDataProvider
     */
    public function testIsSingleInversedRelation($type, $expected)
    {
        $fieldName = 'relation';

        $this->metadata->expects($this->once())
            ->method('getAssociationMapping')
            ->with($fieldName)
            ->will($this->returnValue(array('type' => $type)));

        $this->assertEquals($expected, $this->helper->isSingleInversedRelation(self::TEST_CLASS, $fieldName));
    }

    /**
     * @return array
     */
    public function isSingleInversedRelationDataProvider()
    {
        return array(
            'one to one'   => array(ClassMetadata::ONE_TO_ONE, true),
            'one to many'  => array(ClassMetadata::ONE_TO_MANY, true),
            'many to one'  => array(ClassMetadata::MANY_TO_ONE, false),
            'many to many' => array(ClassMetadata::MANY_TO_MANY, false),
        );
    }
}
