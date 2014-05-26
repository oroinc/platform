<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Entity;

use Oro\Bundle\NoteBundle\Entity\Note;

class NoteTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersSetters()
    {
        $note = new Note();

        $this->assertNull($note->getMessage());
        $this->assertNull($note->getId());

        $now = new \DateTime();
        $note->setCreatedAt($now)
             ->setUpdatedAt($now)
             ->setMessage('test');

        $this->assertEquals($now, $note->getCreatedAt());
        $this->assertEquals($now, $note->getUpdatedAt());
        $this->assertEquals('test', $note->getMessage());

        $user = new \stdClass();
        $note->setUpdatedBy($user);
        $this->assertEquals($user, $note->getUpdatedBy());
    }
}
