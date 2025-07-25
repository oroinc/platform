<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class NavigationItemTest extends TestCase
{
    public function testNavigationItemEntity(): void
    {
        $organization = new Organization();
        $user = new User();
        $user->setEmail('some@email.com');

        $values = [
            'title' => 'Some Title',
            'url' => 'Some Url',
            'position' => 15,
            'user' => $user,
            'organization' => $organization
        ];

        $item = new NavigationItem($values);
        $item->setType('test');
        $this->assertEquals($values['title'], $item->getTitle());
        $this->assertEquals($values['url'], $item->getUrl());
        $this->assertEquals($values['position'], $item->getPosition());
        $this->assertEquals($values['user'], $item->getUser());
        $this->assertEquals($values['organization'], $item->getOrganization());
        $this->assertEquals('test', $item->getType());

        $dateTime = new \DateTime();
        $item->setUpdatedAt($dateTime);
        $this->assertEquals($dateTime, $item->getUpdatedAt());

        $dateTime = new \DateTime();
        $item->setCreatedAt($dateTime);
        $this->assertEquals($dateTime, $item->getCreatedAt());
    }

    public function testDoPrePersist(): void
    {
        $item = new NavigationItem();
        $item->doPrePersist();

        $this->assertInstanceOf('DateTime', $item->getCreatedAt());
        $this->assertInstanceOf('DateTime', $item->getUpdatedAt());
        $this->assertEquals($item->getCreatedAt(), $item->getUpdatedAt());
    }

    public function testDoPreUpdate(): void
    {
        $item = new NavigationItem();
        $item->doPreUpdate();

        $this->assertInstanceOf('DateTime', $item->getUpdatedAt());
    }
}
