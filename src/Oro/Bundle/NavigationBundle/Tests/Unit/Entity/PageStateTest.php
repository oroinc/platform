<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity;

use Oro\Bundle\NavigationBundle\Entity\PageState;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\ReflectionUtil;

class PageStateTest extends \PHPUnit\Framework\TestCase
{
    public function testUser()
    {
        $item = new PageState();
        $user = new User();

        $this->assertNull($item->getId());
        $this->assertNull($item->getUser());

        $item->setUser($user);

        $this->assertEquals($user, $item->getUser());
    }

    public function testDateTime()
    {
        $item = new PageState();
        $dateTime = new \DateTime();

        $item->setUpdatedAt($dateTime);
        $item->setCreatedAt($dateTime);

        $this->assertEquals($dateTime, $item->getUpdatedAt());
        $this->assertEquals($dateTime, $item->getCreatedAt());
    }

    public function testPageId()
    {
        $item = new PageState();
        $pageId = 'SomeId';

        $item->setPageId($pageId);

        $this->assertEquals($pageId, $item->getPageId());
    }

    public function testData()
    {
        $item = new PageState();
        $data = 'test data';

        $item->setData($data);

        $this->assertEquals($data, $item->getData());
    }

    public function testDoPrePersist()
    {
        $item = new PageState();
        $pageId = 'SomeId';
        $userId = 123;
        $user = new User();
        ReflectionUtil::setId($user, $userId);

        $item->setPageId($pageId);
        $item->setUser($user);

        $item->doPrePersist();

        $this->assertInstanceOf('DateTime', $item->getCreatedAt());
        $this->assertInstanceOf('DateTime', $item->getUpdatedAt());
        $this->assertEquals($item->getCreatedAt(), $item->getUpdatedAt());
        $this->assertEquals(PageState::generateHash($pageId, $userId), $item->getPageHash());
    }

    public function testDoPreUpdate()
    {
        $item = new PageState();

        $item->doPreUpdate();

        $this->assertInstanceOf('DateTime', $item->getUpdatedAt());
    }
}
