<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\EventListener\IndexerPrepareQueryListener;
use Oro\Bundle\SearchBundle\Event\IndexerPrepareQueryEvent;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;

class IndexerPrepareQueryListenerTest extends \PHPUnit_Framework_TestCase {

    /** @var \PHPUnit_Framework_MockObject_MockObject|IndexerPrepareQueryListener  */
    protected $indexerPrepareQueryListener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade  */
    protected $securityFacade;
    
    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->indexerPrepareQueryListener  = new IndexerPrepareQueryListener($this->securityFacade);
    }

    public function testUpdateQuery()
    {
        $query = new Query();
        $event = new IndexerPrepareQueryEvent(
            $query,
            IndexerPrepareQueryListener::BUSINESS_UNIT_STRUCTURE_ORGANIZATION
        );

        $user = $this->getUser();
        $this->securityFacade->expects(self::once())->method('getLoggedUser')->willReturn($user);

        $this->indexerPrepareQueryListener->updateQuery($event);
        self::assertEquals($this->getExpectedQuery(), $event->getQuery());
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        $user = new User();
        $user->addOrganization((new Organization())->setId(1));
        $user->addOrganization((new Organization())->setId(2));
        $user->addOrganization((new Organization())->setId(3));

        return $user;
    }

    /**
     * @return Query
     */
    protected function getExpectedQuery()
    {
        $organizationsId = [1,2,3];
        $expectedQuery = new Query();
        $expr = $expectedQuery->getCriteria()->expr();
        $expectedQuery->getCriteria()->andWhere(
            $expr->in('integer.organization', $organizationsId)
        );

        return $expectedQuery;
    }
}
