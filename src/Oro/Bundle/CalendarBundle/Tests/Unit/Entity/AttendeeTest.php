<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

class AttendeeTest extends AbstractEntityTest
{
    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CalendarBundle\Entity\Attendee';
    }

    /**
     * {@inheritDoc}
     */
    public function getSetDataProvider()
    {
        $user          = $this->getMock('Oro\Bundle\UserBundle\Entity\User');
        $calendarEvent = $this->getMock('Oro\Bundle\CalendarBundle\Entity\CalendarEvent');
        $date          = new \DateTime('now');

        return [
            'user'          => ['user', $user, $user],
            'calendarEvent' => ['calendarEvent', $calendarEvent, $calendarEvent],
            'email'         => ['email', 'email@email.com', 'email@email.com'],
            'displayName'   => ['displayName', 'Display Name', 'Display Name'],
            'createdAt'     => ['createdAt', $date, $date],
            'updatedAt'     => ['updatedAt', $date, $date]
        ];
    }

    public function testToString()
    {
        $this->entity->setDisplayName('display name');
        $this->assertEquals('display name', (string) $this->entity);
    }

    public function testLifecycleCallbacks()
    {
        // guards
        $this->assertNull($this->entity->getCreatedAt());
        $this->assertNull($this->entity->getUpdatedAt());

        $this->entity->beforeSave();
        $this->assertInstanceOf('DateTime', $this->entity->getCreatedAt());
        $this->assertInstanceOf('DateTime', $this->entity->getUpdatedAt());

        $originalUpdatedAt = $this->entity->getUpdatedAt();
        $this->entity->preUpdate();
        $this->assertNotSame($originalUpdatedAt, $this->entity->getUpdatedAt());
    }
}
