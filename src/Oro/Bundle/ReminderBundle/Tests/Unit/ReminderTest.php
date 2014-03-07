<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Entity;

use Oro\Bundle\ReminderBundle\Entity\Reminder;

class ReminderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        new Reminder();
    }

    public function testPrePersist()
    {
        $entity = new Reminder();
        $entity->prePersist();

        $this->assertEquals($entity->getCreatedAt()->format('Y-m-d'), date('Y-m-d'));
    }

    public function testPreUpdate()
    {
        $entity = new Reminder();
        $entity->preUpdate();

        $this->assertEquals($entity->getUpdatedAt()->format("m/d/Y H:i"), date("m/d/Y H:i"));
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     */
    public function testSettersAndGetters($value, $property, $getter = null, $setter = null)
    {
        $reminder = new Reminder();

        $getter = $getter ?: 'get' . ucfirst($property);
        $setter = $setter ?: 'set' . ucfirst($property);

        $this->assertEquals($reminder, $reminder->$setter($value));
        $this->assertEquals($value, $reminder->$getter());
    }

    public function settersAndGettersDataProvider()
    {
        return [
            'subject' => [
                'value' => 'value',
                'property' => 'subject',
            ],
            'dueDate' => [
                'value' => new \DateTime(),
                'property' => 'subject',
            ],
        ];
    }
}
