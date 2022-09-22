<?php
declare(strict_types=1);

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Command;

use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class ImportCommandTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadUserData::class]);
    }

    public function testWithoutEmailOption()
    {
        $result = self::runCommand(
            'oro:import:file',
            [
                $this->getFullPathToDataFile('import.csv')
            ]
        );

        self::assertStringContainsString('The --email option is required.', $result);
    }

    public function testInvalidEmail()
    {
        $result = self::runCommand(
            'oro:import:file',
            [
                $this->getFullPathToDataFile('import.csv'),
                '--email' => 'not_existing@example.com'
            ]
        );

        self::assertStringContainsString(
            'Invalid email. There is no user with not_existing@example.com email!',
            $result
        );
    }

    public function testImport()
    {
        /** @var User $importOwner */
        $importOwner = $this->getReference(LoadUserData::SIMPLE_USER);

        $result = self::runCommand(
            'oro:import:file',
            [
                $this->getFullPathToDataFile('import.csv'),
                '--email' => $importOwner->getEmail(),
                '--processor' => 'oro_translation_translation.add_or_replace',
                '--jobName' => 'language_translations_import_from_csv',
            ]
        );

        self::assertStringContainsString('Scheduled successfully.', $result);

        $sentMessage = self::getSentMessage(PreImportTopic::getName());

        // Unset randomly generated fileName for test purposes
        unset($sentMessage['fileName']);

        $expectedMessage = [
            'originFileName' => 'import.csv',
            'userId' => $importOwner->getId(),
            'jobName' => 'language_translations_import_from_csv',
            'processorAlias' => 'oro_translation_translation.add_or_replace',
            'process' => 'import'
        ];

        self::assertEquals($expectedMessage, $sentMessage);
    }

    private function getFullPathToDataFile(string $fileName): string
    {
        $dataDir = self::getContainer()
            ->get('kernel')
            ->locateResource('@OroImportExportBundle/Tests/Functional/Async/Import/fixtures');

        return $dataDir . DIRECTORY_SEPARATOR . $fileName;
    }
}
