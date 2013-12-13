<?php

namespace Oro\Bundle\IntegrationBundle\ImportExport\Writer;

use Oro\Bundle\ImportExportBundle\Writer\EntityWriter;

class PersistentBatchWriter extends EntityWriter
{
    /**
     * {@inheritdoc}
     */
    public function write(array $items)
    {
        try {
            $this->entityManager->beginTransaction();
            foreach ($items as $item) {
                $this->entityManager->persist($item);
                $this->detachFixer->fixEntityAssociationFields($item, 1);
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
