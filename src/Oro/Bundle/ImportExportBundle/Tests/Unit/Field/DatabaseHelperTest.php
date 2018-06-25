<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestEntity;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\TestOrganization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadata;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class DatabaseHelperTest extends \PHPUnit\Framework\TestCase
{
    const TEST_CLASS = 'stdClass';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $metadata;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $ownershipMetadataProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $fieldHelperService;

    /** @var DatabaseHelper */
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

        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->never())->method($this->anything());

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->entityManager));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(self::TEST_CLASS)
            ->will($this->returnValue($this->repository));

        $fieldHelper = $this->createMock(ServiceLink::class);
        $this->fieldHelperService = $this->getMockBuilder('Oro\Bundle\EntityBundle\Helper\FieldHelper')
            ->disableOriginalConstructor()
            ->getMock('getService');
        $fieldHelper->expects($this->any())
            ->method('getService')
            ->willReturn($this->fieldHelperService);

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $ownershipMetadataProviderLink = $this->createMock(ServiceLink::class);
        $this->ownershipMetadataProvider = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\Owner\Metadata\OwnershipMetadataProviderInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $ownershipMetadataProviderLink->expects($this->any())
            ->method('getService')
            ->willReturn($this->ownershipMetadataProvider);

        $this->helper = new DatabaseHelper(
            $registry,
            $this->doctrineHelper,
            $fieldHelper,
            $this->tokenAccessor,
            $ownershipMetadataProviderLink
        );
    }

    public function testFind()
    {
        $entity = new \stdClass();
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->will($this->returnValue($entity));

        $found = $this->helper->find(self::TEST_CLASS, $identifier);
        $this->assertEquals($entity, $found);

        $this->assertSame($found, $this->helper->find(self::TEST_CLASS, $identifier));
    }

    public function testFindObjectFromEnotherOrganization()
    {
        $entityOrganization = new TestOrganization();
        $entityOrganization->setId(2);
        $entity = new TestEntity();
        $entity->getOrganization($entityOrganization);
        $identifier = 1;
        $entity->setId($identifier);

        $currentOrganization = new TestOrganization();
        $currentOrganization->setId(1);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganization')
            ->willReturn($currentOrganization);
        $this->tokenAccessor->expects($this->any())
            ->method('getOrganizationId')
            ->willReturn($currentOrganization->getId());

        $this->doctrineHelper->expects($this->once())
            ->method('getEntity')
            ->with(self::TEST_CLASS, $identifier)
            ->will($this->returnValue($entity));

        $metadata = new OwnershipMetadata(
            'USER',
            'owner',
            'owner',
            'organization',
            'organization'
        );

        $this->fieldHelperService->expects($this->once())
            ->method('getObjectValue')
            ->willReturn($entity->getOrganization());
        
        $this->ownershipMetadataProvider->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

        $this->assertNull($this->helper->find(self::TEST_CLASS, $identifier));
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
        return [
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
        ];
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
        return [
            'mapped by field' => [
                'association' => ['mappedBy' => 'field'],
                'expectedField' => 'field',
            ],
            'inversed by field' => [
                'association' => ['inversedBy' => 'field'],
                'expectedField' => 'field',
            ],
            'no inversed field' => [
                'association' => [],
                'expectedField' => null,
            ],
        ];
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
            ->will($this->returnValue(['type' => $type]));

        $this->assertEquals($expected, $this->helper->isSingleInversedRelation(self::TEST_CLASS, $fieldName));
    }

    /**
     * @return array
     */
    public function isSingleInversedRelationDataProvider()
    {
        return [
            'one to one'   => [ClassMetadata::ONE_TO_ONE, true],
            'one to many'  => [ClassMetadata::ONE_TO_MANY, true],
            'many to one'  => [ClassMetadata::MANY_TO_ONE, false],
            'many to many' => [ClassMetadata::MANY_TO_MANY, false],
        ];
    }

    public function testGetEntityReference()
    {
        $entity = new \stdClass();
        $reference = new \stdClass();
        $entityName = get_class($entity);
        $identifier = 1;

        $this->doctrineHelper->expects($this->once())
            ->method('getSingleEntityIdentifier')
            ->with($entity)
            ->will($this->returnValue($identifier));
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityReference')
            ->with($entityName, $identifier)
            ->will($this->returnValue($reference));

        $this->assertEquals($reference, $this->helper->getEntityReference($entity));
    }

    public function testFindOneByCacheWithoutEntity()
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn(null);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects($this->any())
            ->method('andWhere')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setParameters')
            ->willReturnSelf();
        $queryBuilder->expects($this->any())
            ->method('setMaxResults')
            ->willReturnSelf();
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);

        $this->repository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $this->assertNull($this->helper->findOneBy(self::TEST_CLASS, ['field1' => 'value1', 'field2' => 'value2']));

        //check cache
        $this->assertNull($this->helper->findOneBy(self::TEST_CLASS, ['field2' => 'value2', 'field1' => 'value1']));
    }
}
