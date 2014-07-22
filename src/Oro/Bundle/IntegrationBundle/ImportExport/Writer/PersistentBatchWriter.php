<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Akeneo\Bundle\BatchBundle\Item\ItemWriterInterface;

class PersistentBatchWriter implements ItemWriterInterface
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var EntityManager */
    protected $em;

    /**
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        $this->ensureEntityManagerReady();

        try {
            $this->em->beginTransaction();

            foreach ($items as $item) {
                $this->em->persist($item);
            }

            $this->em->flush();

            $this->em->commit();
            $this->em->clear();
        } catch (\Exception $exception) {
            $this->em->rollback();

            throw $exception;
        }
    }

    /**
     * Prepares EntityManager, reset it if closed with error
     */
    protected function ensureEntityManagerReady()
    {
        $this->em = $this->registry->getManager();

        if (!$this->em->isOpen()) {
            $this->registry->resetManager();
            $this->ensureEntityManagerReady();
        }
    }
}
