<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Oro\Bundle\SoapBundle\Handler\DeleteHandlerInterface;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;

interface ActivityEntityDeleteHandlerInterface extends DeleteHandlerInterface
{
    /**
     * @param RelationIdentifier $relationIdentifier
     *
     * @return bool
     */
    public function isApplicable(RelationIdentifier $relationIdentifier);
}
