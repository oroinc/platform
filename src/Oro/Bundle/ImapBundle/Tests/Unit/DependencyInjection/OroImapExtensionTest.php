<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImapBundle\Controller as ImapControllers;
use Oro\Bundle\ImapBundle\DependencyInjection\OroImapExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroImapExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroImapExtension());

        $expectedDefinitions = [
            ImapControllers\CheckConnectionController::class,
            ImapControllers\ConnectionController::class,
            ImapControllers\GmailAccessTokenController::class,
            ImapControllers\MicrosoftAccessTokenController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
