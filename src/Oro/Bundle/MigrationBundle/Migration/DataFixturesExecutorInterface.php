<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\FixtureInterface;

/**
 * Provides an interface of data fixtures executor.
 */
interface DataFixturesExecutorInterface
{
    /** Data fixtures contain the main data for an application */
    const MAIN_FIXTURES = 'main';

    /** Data fixtures contain the demo data for an application */
    const DEMO_FIXTURES = 'demo';

    /**
     * Executes the given data fixtures.
     *
     * @param FixtureInterface[] $fixtures     The list of data fixtures to execute
     * @param string             $fixturesType The type of data fixtures
     * @param ?callable $progressCallback Callback that can be used to track execution progress. The callback should
     * expect to receive two arguments - memory consumption (int, bytes) and duration (int|float, milliseconds).
     */
    public function execute(array $fixtures, string $fixturesType, ?callable $progressCallback): void;

    /**
     * Sets a logger callback for logging messages when executing data fixtures.
     *
     * @param callable|null $logger
     */
    public function setLogger($logger);
}
