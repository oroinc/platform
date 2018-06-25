<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\SearchBundle\Engine\Indexer;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Autocomplete\AssignedToOrganizationUsersHandler;
use Oro\Bundle\UserBundle\Entity\User;

class AssignedToOrganizationUsersHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var AssignedToOrganizationUsersHandler */
    protected $handler;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $searchIndexer;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $manager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $tokenAccessor;

    public function setUp()
    {
        $attachmentManager = $this->createMock(AttachmentManager::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->searchIndexer = $this->createMock(Indexer::class);
        $this->repository = $this->createMock(EntityRepository::class);
        $metadata = $this->createMock(ClassMetadata::class);

        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(User::class)
            ->will($this->returnValue($metadata));

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($this->repository));
        $this->manager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->handler = new AssignedToOrganizationUsersHandler($attachmentManager, User::class, []);
        $this->handler->setTokenAccessor($this->tokenAccessor);
        $this->handler->initSearchIndexer($this->searchIndexer, [User::class => ['alias' => 'user']]);
        $this->handler->initDoctrinePropertiesByEntityManager($this->manager);
    }

    public function testSearchWithOrganizationInToken()
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
        $this->searchIndexer->expects($this->at(1))
            ->method('setIsAllowedApplyAcl')
            ->with(false);
        $this->searchIndexer->expects($this->at(3))
            ->method('setIsAllowedApplyAcl')
            ->with(true);
        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($searchResult);

        $this->handler->search('test', 1, 10);

        $expectedExpression = new Comparison('integer.assigned_organization_id', '=', new Value(1));

        $this->assertEquals($expectedExpression, $query->getCriteria()->getWhereExpression());
    }

    public function testSearchWithoutOrganizationInToken()
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
        $this->searchIndexer->expects($this->at(1))
            ->method('setIsAllowedApplyAcl')
            ->with(false);
        $this->searchIndexer->expects($this->at(3))
            ->method('setIsAllowedApplyAcl')
            ->with(true);
        $this->searchIndexer->expects($this->once())
            ->method('query')
            ->with($query)
            ->willReturn($searchResult);

        $this->handler->search('test', 1, 10);

        $this->assertNull($query->getCriteria()->getWhereExpression());
    }
}
