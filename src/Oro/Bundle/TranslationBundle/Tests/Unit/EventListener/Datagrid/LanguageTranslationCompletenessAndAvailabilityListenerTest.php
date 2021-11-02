<?php
declare(strict_types=1);

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener\Datagrid;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\TranslationBundle\Download\TranslationMetricsProviderInterface;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\EventListener\Datagrid\LanguageTranslationCompletenessAndAvailabilityListener;

class LanguageTranslationCompletenessAndAvailabilityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationMetricsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translationMetricsProvider;

    /** @var TranslationKeyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $translationKeyRepository;

    /** @var LanguageTranslationCompletenessAndAvailabilityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->translationMetricsProvider = $this->createMock(TranslationMetricsProviderInterface::class);
        $this->translationKeyRepository = $this->createMock(TranslationKeyRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(TranslationKey::class)
            ->willReturn($this->translationKeyRepository);

        $this->listener = new LanguageTranslationCompletenessAndAvailabilityListener(
            $this->translationMetricsProvider,
            $doctrine
        );
    }

    public function testSetsTranslationCompletenessAndAvailability(): void
    {
        $this->translationKeyRepository->expects(self::any())
            ->method('getCount')
            ->willReturn(100);

        $today = new \DateTime('now', new \DateTimeZone('UTC'));
        $yesterday = new \DateTime('yesterday', new \DateTimeZone('UTC'));

        $originalRecords = [];
        $expectedRecords = [];
        $languageMetrics = [];

        // Install available:

        $originalRecords[] = new ResultRecord([
            'code' => 'uk_UA',
            'translationCount' => 99,
            'installedBuildDate' => null,
        ]);
        $languageMetrics[] = ['uk_UA', ['lastBuildDate' => $today]];
        $expectedRecords[] = new ResultRecord([
            'code' => 'uk_UA',
            'translationCount' => 99,
            'translationCompleteness' => 0.99,
            'installedBuildDate' => null,
            'translationStatus' => 'install_available',
        ]);

        // Update available:

        $originalRecords[] = new ResultRecord([
            'code' => 'de_DE',
            'translationCount' => 99,
            'installedBuildDate' => $yesterday,
        ]);
        $languageMetrics[] = ['de_DE', ['lastBuildDate' => $today]];
        $expectedRecords[] = new ResultRecord([
            'code' => 'de_DE',
            'translationCount' => 99,
            'translationCompleteness' => 0.99,
            'installedBuildDate' => $yesterday,
            'translationStatus' => 'update_available',
        ]);

        // Up-to-date:

        $originalRecords[] = new ResultRecord([
            'code' => 'fr_FR',
            'translationCount' => 99,
            'installedBuildDate' => $today,
        ]);
        $languageMetrics[] = ['fr_FR', ['lastBuildDate' => $yesterday]];
        $expectedRecords[] = new ResultRecord([
            'code' => 'fr_FR',
            'translationCount' => 99,
            'translationCompleteness' => 0.99,
            'installedBuildDate' => $today,
            'translationStatus' => 'up_to_date',
        ]);

        // No available translations:

        $originalRecords[] = new ResultRecord([
            'code' => 'fr_CA',
            'translationCount' => 99,
            'installedBuildDate' => null,
        ]);
        $languageMetrics[] = ['fr_FR', []];
        $expectedRecords[] = new ResultRecord([
            'code' => 'fr_CA',
            'translationCount' => 99,
            'translationCompleteness' => 0.99,
            'installedBuildDate' => null,
            'translationStatus' => 'not_available',
        ]);

        $this->translationMetricsProvider->expects(self::any())
            ->method('getForLanguage')
            ->willReturnMap($languageMetrics);
        $event = new OrmResultAfter($this->createMock(DatagridInterface::class), $originalRecords);

        ($this->listener)($event);

        self::assertEquals($expectedRecords, $event->getRecords());
    }

    public function testSetTranslationCompletenessNullIfZeroTranslationKeyCount(): void
    {
        $this->translationKeyRepository->expects(self::any())
            ->method('getCount')
            ->willReturn(null);

        $this->translationMetricsProvider->expects(self::any())
            ->method('getForLanguage')
            ->willReturn(null);
        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [new ResultRecord(['code' => 'uk_UA', 'translationCount' => 99])]
        );

        ($this->listener)($event);

        self::assertNull($event->getRecords()[0]->getValue('translationCompleteness'));
    }
}
