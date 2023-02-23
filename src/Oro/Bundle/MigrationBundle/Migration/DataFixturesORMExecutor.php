<?php

namespace Oro\Bundle\MigrationBundle\Migration;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareInterface;
use Oro\Bundle\LocaleBundle\DataFixtures\LocalizationOptionsAwareTrait;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * This ORM executor prevents hiding an exception that is happened during executing a data fixture
 * in case the entity manager cannot be closed or the database transaction rollback failed.
 */
class DataFixturesORMExecutor extends ORMExecutor implements LocalizationOptionsAwareInterface
{
    use LocalizationOptionsAwareTrait;

    /** @var callable|null */
    private $progressCallback = null;

    /**
     * {@inheritDoc}
     */
    public function execute(array $fixtures, $append = false)
    {
        $em = $this->getObjectManager();
        $connection = $em->getConnection();
        $connection->beginTransaction();
        $stopwatch = null;
        try {
            if ($append === false) {
                $this->purge();
            }

            foreach ($fixtures as $fixture) {
                if ($fixture instanceof LocalizationOptionsAwareInterface) {
                    $fixture->setFormattingCode($this->formattingCode);
                    $fixture->setLanguage($this->language);
                }
                $name = \get_class($fixture);
                if (null !== $this->progressCallback) {
                    $stopwatch = new Stopwatch();
                    $stopwatch->start($name);
                }
                $this->load($em, $fixture);
                if (null !== $this->progressCallback) {
                    $stopwatch->stop($name);
                    ($this->progressCallback)(
                        $stopwatch->getEvent($name)->getMemory(),
                        $stopwatch->getEvent($name)->getDuration()
                    );
                }
            }

            $em->flush();
            $connection->commit();
        } catch (\Throwable $e) {
            try {
                $em->close();
                $connection->rollBack();
            } catch (\Throwable $rollbackException) {
                // ignore any exceptions here to prevent hiding the original exception
            }

            throw $e;
        }
    }

    /**
     * @param ?callable $progressCallback
     *
     * @return $this
     */
    public function setProgressCallback(?callable $progressCallback): self
    {
        $this->progressCallback = $progressCallback;
        return $this;
    }
}
