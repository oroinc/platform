<?php

namespace Oro\Bundle\NoteBundle\Tests\Functional\Action;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\NoteBundle\Tests\Functional\DataFixtures\LoadNoteTargets;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CreateNoteActionTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadNoteTargets::class]);
    }

    /**
     * @dataProvider argumentTypes
     * @param array $arguments
     */
    public function testCreateAction(array $arguments)
    {
        /** @var TestActivityTarget $target */
        $target = $this->getReference(LoadNoteTargets::TARGET_ONE);

        $assembler = self::getContainer()->get('oro_action.action_assembler');
        $context = new ActionData(['entity' => $target]);

        $action = $assembler->assemble([['@create_note' => $arguments]]);
        $action->execute($context);

        /** @var Note $note */
        $note = $context->get('note_attribute');

        $this->assertInstanceOf(Note::class, $note);
        $this->assertEquals('message text', $note->getMessage());

        list($noteAssociated) = self::getContainer()->get('oro_note.manager')->getList(
            TestActivityTarget::class,
            $target->getId(),
            'DESC'
        );

        $this->assertEquals($noteAssociated->getMessage(), $note->getMessage());
    }

    /**
     * @return \Generator
     */
    public function argumentTypes()
    {
        yield 'full' => [
            'arguments' => [
                'message' => 'message text',
                'target_entity' => '$.entity',
                'attribute' => '$.note_attribute'
            ]
        ];
        yield 'inline' => ['arguments' => ['message text', '$.entity', '$.note_attribute']];
    }
}
