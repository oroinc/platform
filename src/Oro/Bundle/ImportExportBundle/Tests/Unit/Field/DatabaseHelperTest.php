<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

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
        $criteria = ['id' => 1];

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with($criteria)
            ->will($this->returnValue($entity));

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
}
