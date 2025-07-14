<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AttachmentBundle\Provider\PictureSourcesProviderInterface;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Autocomplete\AssignedToOrganizationUsersHandler;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssignedToOrganizationUsersHandlerTest extends TestCase
{
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private Indexer&MockObject $searchIndexer;
    private EntityRepository&MockObject $repository;
    private ObjectManager&MockObject $manager;
    private AssignedToOrganizationUsersHandler $handler;

    #[\Override]
    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->searchIndexer = $this->createMock(Indexer::class);
        $this->repository = $this->createMock(EntityRepository::class);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(User::class)
            ->willReturn($metadata);

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($this->repository);
        $this->manager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $searchMappingProvider = $this->createMock(SearchMappingProvider::class);
        $searchMappingProvider->expects($this->once())
            ->method('getEntityAlias')
            ->with(User::class)
            ->willReturn('user');

        $pictureSourcesProvider = $this->createMock(PictureSourcesProviderInterface::class);

        $this->handler = new AssignedToOrganizationUsersHandler($pictureSourcesProvider, User::class, []);
        $this->handler->setTokenAccessor($this->tokenAccessor);
        $this->handler->initSearchIndexer($this->searchIndexer, $searchMappingProvider);
        $this->handler->initDoctrinePropertiesByEntityManager($this->manager);
    }

    public function testSearchWithOrganizationInToken(): void
    {
        $query = new Query();
        $searchResult = new Result($query);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(1);
        $this->searchIndexer->expects($this->once())
            ->method('getSimpleSearchQuery')
            ->with('test', 0, 11, 'user')
            ->willReturn($query);
        $this->searchIndexer->expects($this->exactly(2))
            ->method('setIsAllowedApplyAcl')
            ->withConsecutive([false], [true]);
        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($searchResult);

        $this->handler->search('test', 1, 10);

        $expectedExpression = new Comparison('integer.assigned_organization_id', '=', new Value(1));

        $this->assertEquals($expectedExpression, $query->getCriteria()->getWhereExpression());
    }

    public function testSearchWithoutOrganizationInToken(): void
    {
        $query = new Query();
        $searchResult = new Result($query);

        $this->tokenAccessor->expects($this->once())
            ->method('getOrganizationId')
            ->willReturn(null);
        $this->searchIndexer->expects($this->once())
            ->method('getSimpleSearchQuery')
            ->with('test', 0, 11, 'user')
            ->willReturn($query);
        $this->searchIndexer->expects($this->exactly(2))
            ->method('setIsAllowedApplyAcl')
            ->withConsecutive([false], [true]);
        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($searchResult);

        $this->handler->search('test', 1, 10);

        $this->assertNull($query->getCriteria()->getWhereExpression());
    }
}
