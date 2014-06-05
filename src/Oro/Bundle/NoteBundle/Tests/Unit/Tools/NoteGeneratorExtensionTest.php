<?php


namespace Oro\Bundle\NoteBundle\Tests\Unit\Tools;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendEntityGenerator;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Tools\NoteExtendEntityGeneratorExtension;

class NoteGeneratorExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var NoteExtendEntityGeneratorExtension */
    protected $extension;

    public function setUp()
    {
        $this->extension = new NoteExtendEntityGeneratorExtension();
    }

    public function testSupports()
    {
        $schemas = [];
        $result = $this->extension->supports(ExtendEntityGenerator::ACTION_PRE_PROCESS, $schemas);
        $this->assertFalse($result, 'Pre processing not supported');

        $result = $this->extension->supports(
            ExtendEntityGenerator::ACTION_GENERATE,
            ['class' => 'Test\Entity', 'relation' => 'test']
        );
        $this->assertFalse($result, 'Generate action, entity not matched');

        $result = $this->extension->supports(
            ExtendEntityGenerator::ACTION_GENERATE,
            ['class' => Note::ENTITY_NAME]
        );
        $this->assertFalse($result, 'Generate action, entity matched, relation absent');
    }
}
