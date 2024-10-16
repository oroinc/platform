<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\Fixture\LoadAllEnumOptionTranslationsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class ActualizeEnumOptionTranslationsCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    private Connection $connection;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadAllEnumOptionTranslationsData::class
        ]);

        $this->connection = self::getContainer()->get('doctrine')->getConnection();
    }

    public function testExecute()
    {
        $doctrine = self::getContainer()->get('doctrine');
        /** @var EntityRepository $repo */
        $repo = $doctrine->getManager()->getRepository(EnumOptionTranslation::class);
        self::assertTrue($repo->count([]) > 0);
        $this->clearEnumOptionTranslation();
        self::assertEquals(0, $repo->count([]));

        $commandTester = $this->doExecuteCommand('oro:entity-extend:actualize:enum-option-translations');
        self::assertTrue($repo->count([]) > 0);

        $this->assertOutputContains($commandTester, 'Enum Option Trnanslations actualized for "en" locale');
        $this->assertOutputContains($commandTester, 'Enum Option Trnanslations actualized for "fr_FR" locale');
    }

    private function clearEnumOptionTranslation(): void
    {
        $this->connection->executeQuery("DELETE FROM oro_enum_option_trans");
    }
}
