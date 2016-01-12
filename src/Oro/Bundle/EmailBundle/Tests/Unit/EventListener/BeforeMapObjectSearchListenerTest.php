<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\EventListener\BeforeMapObjectSearchListener;
use Oro\Bundle\SearchBundle\Event\SearchMappingCollectEvent;

class BeforeMapObjectSearchListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var BeforeMapObjectSearchListener */
    protected $listener;

    public function setUp()
    {
        $this->listener = new BeforeMapObjectSearchListener();
    }

    public function testAddEntityMapTitleFieldEvent()
    {
        $emailUserConfig = [BeforeMapObjectSearchListener::EMAIL_USER_CLASS_NAME => [
            'title_fields' => ['email.subject'], 'alias' => '']
        ];
        $expectedConfig = $emailUserConfig;
        $expectedConfig[BeforeMapObjectSearchListener::EMAIL_CLASS_NAME] =
            ['title_fields' => ['subject'], 'alias' => ''];
        $event = new SearchMappingCollectEvent($emailUserConfig);
        $this->listener->addEntityMapTitleFieldEvent($event);
        $this->assertEquals($expectedConfig, $event->getMappingConfig());
    }
}
