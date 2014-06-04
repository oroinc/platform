<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;

class PersistentBatchWriter extends EntityWriter
{
    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        try {
            $this->entityManager->beginTransaction();
            foreach ($items as $item) {
                $this->entityManager->persist($item);
            }
            $this->entityManager->commit();
        } catch (\Exception $exception) {
            $this->entityManager->rollback();

            throw $exception;
        }
        $this->entityManager->flush();
        $this->entityManager->clear();
    }
}
