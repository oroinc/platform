<?php

namespace Oro\Bundle\NoteBundle\Entity\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager as BaseApiEntityManager;

class ApiEntityManager extends BaseApiEntityManager
{
    /**
     * {@inheritdoc}
     */
    public function __construct($class, ObjectManager $om)
    {
        parent::__construct($class, $om);
    }

    public function find($id)
    {
        $result = parent::find($id);

        return $result;
    }
}
