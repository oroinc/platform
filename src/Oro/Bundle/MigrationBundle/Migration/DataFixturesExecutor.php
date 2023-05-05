<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareInterface;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareTrait;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\MigrationBundle\Event\MigrationEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Executes ORM data fixtures.
 */
class DataFixturesExecutor implements DataFixturesExecutorInterface, LocalizationOptionsAwareInterface
{
    use LocalizationOptionsAwareTrait;

    /** @var EntityManagerInterface */
    private $em;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var callable|null */
    private $logger;

    public function __construct(
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        string $language,
        string $formattingCode
    ) {
        $this->em = $em;
        $this->eventDispatcher = $eventDispatcher;
        $this->language = $language;
        $this->formattingCode = $formattingCode;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(array $fixtures, string $fixturesType, ?callable $progressCallback = null): void
    {
        $event = new MigrationDataFixturesEvent($this->em, $fixturesType, $this->logger);
        $this->eventDispatcher->dispatch($event, MigrationEvents::DATA_FIXTURES_PRE_LOAD);

        $executor = new DataFixturesORMExecutor($this->em);
        $executor->setFormattingCode($this->formattingCode);
        $executor->setLanguage($this->language);
        $executor->setProgressCallback($progressCallback);
        if (null !== $this->logger) {
            $executor->setLogger($this->logger);
        }
        $executor->execute($fixtures, true);

        $this->eventDispatcher->dispatch($event, MigrationEvents::DATA_FIXTURES_POST_LOAD);
    }

    /**
     * {@inheritdoc}
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }
}
