<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class NavigationHistoryItemTest extends \PHPUnit\Framework\TestCase
{
    public function testNavigationHistoryItemEntity()
    {
        $organization = new Organization();
        $user = new User();
        $user->setEmail('some@email.com');

        $values = array(
            'title'           => 'Some Title',
            'url'             => 'Some Url',
            'user'            => $user,
            'organization'    => $organization,
            'route'           => 'test_route',
            'routeParameters' => array('key' => 'value'),
            'entityId'        => 1,

        );

        $item = new NavigationHistoryItem($values);
        $this->assertEquals($values['title'], $item->getTitle());
        $this->assertEquals($values['url'], $item->getUrl());
        $this->assertEquals($values['user'], $item->getUser());
        $this->assertEquals($values['organization'], $item->getOrganization());
        $this->assertEquals($values['route'], $item->getRoute());
        $this->assertEquals($values['routeParameters'], $item->getRouteParameters());
        $this->assertEquals($values['entityId'], $item->getEntityId());

        $dateTime = new \DateTime();
        $item->setVisitedAt($dateTime);
        $this->assertEquals($dateTime, $item->getVisitedAt());

        $visitCount = rand(0, 100);
        $item->setVisitCount($visitCount);
        $this->assertEquals($visitCount, $item->getVisitCount());

        $this->assertEquals(null, $item->getId());
    }

    public function testDoPrePersist()
    {
        $item = new NavigationHistoryItem();
        $item->doPrePersist();

        $this->assertInstanceOf('DateTime', $item->getVisitedAt());
        $this->assertEquals($item->getVisitCount(), 0);
    }

    public function testDoUpdate()
    {
        $item = new NavigationHistoryItem();
        $oldVisitedAt = $item->getVisitedAt();
        $oldVisitCount = $item->getVisitCount();

        $item->doUpdate();

        $this->assertInstanceOf('DateTime', $item->getVisitedAt());
        $this->assertNotEquals($oldVisitedAt, $item->getVisitedAt());
        $this->assertNotEquals($oldVisitCount, $item->getVisitCount());
        $this->assertEquals($oldVisitCount + 1, $item->getVisitCount());
    }
}
