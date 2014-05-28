<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\NoteBundle\Form\Type\NoteEnabledChoiceType;

class NoteEnabledChoiceTypeTest extends TypeTestCase
{
    /** @var NoteEnabledChoiceType */
    protected $type;

    protected function setUp()
    {
         $this->type = new NoteEnabledChoiceType();
    }

    public function testGetName()
    {
        $this->assertEquals('note_choice', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('choice', $this->type->getParent());
    }
}
