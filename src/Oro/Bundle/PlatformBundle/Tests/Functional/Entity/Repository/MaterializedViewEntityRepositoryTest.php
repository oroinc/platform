<?php

namespace Oro\Bundle\PlatformBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\PlatformBundle\Entity\MaterializedView as MaterializedViewEntity;
use Oro\Bundle\PlatformBundle\Entity\Repository\MaterializedViewEntityRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class MaterializedViewEntityRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    /**
     * @dataProvider findOlderThanDataProvider
     */
    public function testFindOlderThan(\DateTimeInterface $dateTime, array $expected): void
    {
        $this->loadFixtures(['@OroPlatformBundle/Tests/Functional/DataFixtures/orphaned_materialized_views.yml']);

        /** @var MaterializedViewEntityRepository $repository */
        $repository = self::getContainer()->get('doctrine')->getRepository(MaterializedViewEntity::class);

        $expected = array_map(fn (string $name) => $this->getReference($name)->getName(), $expected);

        self::assertEqualsCanonicalizing($expected, $repository->findOlderThan($dateTime));
    }

    public function findOlderThanDataProvider(): array
    {
        return [
            ['dateTime' => new \DateTime('today -10 days', new \DateTimeZone('UTC')), 'expected' => []],
            [
                'dateTime' => new \DateTime('today -7 days', new \DateTimeZone('UTC')),
                'expected' => ['materialized_view_7_days_old'],
            ],
            [
                'dateTime' => new \DateTime('today -2 days', new \DateTimeZone('UTC')),
                'expected' => ['materialized_view_2_days_old', 'materialized_view_7_days_old'],
            ],
            [
                'dateTime' => new \DateTime('now', new \DateTimeZone('UTC')),
                'expected' => [
                    'materialized_view_0_days_old',
                    'materialized_view_2_days_old',
                    'materialized_view_7_days_old',
                ],
            ],
        ];
    }
}
