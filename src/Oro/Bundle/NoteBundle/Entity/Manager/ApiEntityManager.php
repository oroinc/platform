<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

class ApiEntityManager extends BaseApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        /** @var Note $result */
        $result = parent::find($id);
        if (!$result) {
            throw new EntityNotFoundException();
        }

        return $result;
    }
}
