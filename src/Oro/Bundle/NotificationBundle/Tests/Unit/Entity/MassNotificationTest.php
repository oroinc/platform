<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Entity;

use Oro\Bundle\NotificationBundle\Entity\MassNotification;

class MassNotificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MassNotification
     */
    protected $massNotification;

    protected function setUp()
    {
        $this->massNotification = new MassNotification();

        // get id should return null cause this entity was not loaded from DB
        $this->assertNull($this->massNotification->getId());
    }

    protected function tearDown()
    {
        unset($this->massNotification);
    }
    
    /**
     * @dataProvider getSetDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array(array($this->massNotification, 'set' . ucfirst($property)), array($value));
        $this->assertEquals(
            $expected,
            call_user_func_array(array($this->massNotification, 'get' . ucfirst($property)), array())
        );
    }

    /**
     * @return array
     */
    public function getSetDataProvider()
    {
        $date = new \DateTime('now');
        return [
            'email'       => ['email', 'test@test.com', 'test@test.com'],
            'sender'      => ['sender', 'from@test.com', 'from@test.com'],
            'body'        => ['body', 'test body', 'test body'],
            'subject'     => ['subject', 'test title', 'test title'],
            'scheduledAt' => ['scheduledAt', $date, $date],
            'processedAt' => ['processedAt', $date, $date],
            'status'      => ['status', 1, 1],
        ];
    }
}
