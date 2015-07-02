<?php

namespace Oro\Bundle\DataAuditBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;

use Oro\Bundle\DataAuditBundle\Entity\Audit;

class DeprecatedAuditDataListener
{
    /** @var Audit[] */
    protected $brokenAudits = [];

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $uow = $args->getEntityManager()->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates()
        );

        $brokenAudits = array_filter($entities, function ($entity) {
            return $entity instanceof Audit && $entity->getDeprecatedData();
        });

        $this->brokenAudits = array_merge($this->brokenAudits, $brokenAudits);
    }

    /**
     * @param PostFlushEventArgs $args
     */
    public function postFlush(PostFlushEventArgs $args)
    {
        if (!$this->brokenAudits) {
            return;
        }

        $em = $args->getEntityManager();
        foreach ($this->brokenAudits as $audit) {
            $meta = $em->getClassMetadata($audit->getObjectClass());
            $deprecatedData = $audit->getDeprecatedData();
            foreach ($deprecatedData as $field => $values) {
                $oldValue = is_array($values['old']) ? $values['old']['value'] : $values['old'];
                $newValue = is_array($values['new']) ? $values['new']['value'] : $values['new'];
                $fieldType = $meta->getTypeOfField($field);

                $audit->createField($field, $fieldType, $newValue, $oldValue);
            }
            $audit->setData(null);
        }

        $this->brokenAudits = [];
        $em->flush();
    }
}
