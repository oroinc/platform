<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Autocomplete;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\UserBundle\Autocomplete\AssignedToOrganizationUsersHandler;
use Oro\Bundle\UserBundle\Entity\User;

class AssignedToOrganizationUsersHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AssignedToOrganizationUsersHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $searchIndexer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    public function setUp()
    {
        $attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchIndexer = $this->getMockBuilder('Oro\Bundle\SearchBundle\Engine\Indexer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $metadata->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->will($this->returnValue('id'));

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->with(User::class)
            ->will($this->returnValue($metadata));

        $this->manager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(User::class)
            ->will($this->returnValue($this->repository));
        $this->manager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->handler = new AssignedToOrganizationUsersHandler(
            $attachmentManager,
            'Oro\Bundle\UserBundle\Entity\User',
            []
        );
        $this->handler->setSecurityFacade($this->securityFacade);
        $this->handler->initSearchIndexer(
            $this->searchIndexer,
            ['Oro\Bundle\UserBundle\Entity\User' => ['alias' => 'user']]
        );
        $this->handler->initDoctrinePropertiesByEntityManager($this->manager);
    }

    public function testSearchWithOrganizationInToken()
    {
        $query = new Query();
        $searchResult = new Result($query);

        $this->securityFacade->expects($this->once())
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

        $this->securityFacade->expects($this->once())
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
