<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityNotFoundException;

use Oro\Bundle\EntityBundle\Model\EntityIdSoap;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

class ApiEntityManager extends BaseApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $result = parent::find($id);
        if (!$result) {
            throw new EntityNotFoundException();
        }

        $entityId = null;
        $target   = $result->getTarget();
        if ($target) {
            $entityId = new EntityIdSoap();
            $entityId
                ->setEntity(ClassUtils::getClass($target))
                ->setId($target->getId());
            $result->entityId = $entityId;
        } else {
            throw new \LogicException('Note entity cannot be unassigned.');
        }

        return $result;
    }
}
