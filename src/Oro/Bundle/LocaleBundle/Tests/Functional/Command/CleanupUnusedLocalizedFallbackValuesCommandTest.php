<?php
declare(strict_types=1);

namespace Oro\Bundle\LocaleBundle\Tests\Functional\Command;

use Oro\Bundle\LocaleBundle\Command\CleanupUnusedLocalizedFallbackValuesCommand;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CleanupUnusedLocalizedFallbackValuesCommandTest extends WebTestCase
{
    private ?LocalizedFallbackValueRepository $repository = null;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            '@OroLocaleBundle/Tests/Functional/DataFixtures/unused_localized_fallback_values_data.yml'
        ]);

        $registry = self::getContainer()->get('doctrine');

        $this->repository = $registry->getManagerForClass(LocalizedFallbackValue::class)
            ->getRepository(LocalizedFallbackValue::class);
    }

    public function testCleanup(): void
    {
        $numberOfUnusedLocalizedFallbackValuesBeforeCommand = $this->repository->count(
            ['string' => 'Unused Localized Fallback Value']
        );

        self::assertGreaterThanOrEqual(5, $numberOfUnusedLocalizedFallbackValuesBeforeCommand);

        $result = self::runCommand(CleanupUnusedLocalizedFallbackValuesCommand::getDefaultName());

        self::assertStringContainsString('Removing unused localized fallback values completed.', $result);

        $numberOfUnusedLocalizedFallbackValuesAfterCommand = $this->repository->count(
            ['string' => 'Unused Localized Fallback Value']
        );

        self::assertEquals(0, $numberOfUnusedLocalizedFallbackValuesAfterCommand);
    }
}
