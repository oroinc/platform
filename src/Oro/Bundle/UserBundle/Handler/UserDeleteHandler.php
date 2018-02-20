<?php

namespace Oro\Bundle\UserBundle\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;

class UserDeleteHandler extends DeleteHandler
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function setTokenAccessor(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkPermissions($entity, ObjectManager $em)
    {
        $loggedUserId = $this->tokenAccessor->getUserId();
        if ($loggedUserId && $loggedUserId == $entity->getId()) {
            throw new ForbiddenException('self delete');
        }
        parent::checkPermissions($entity, $em);
    }
}
