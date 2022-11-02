<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NoteBundle\Controller\Api\Rest\NoteController;
use Oro\Bundle\NoteBundle\DependencyInjection\OroNoteExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroNoteExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroNoteExtension());

        $expectedDefinitions = [
            NoteController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
