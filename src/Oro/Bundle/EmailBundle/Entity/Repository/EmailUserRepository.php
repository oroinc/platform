<?php

namespace Oro\Bundle\EmailBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\UserEmailOwner;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserRepository extends EntityRepository
{
    /**
     * @param Email $email
     * @param User  $user
     *
     * @return null|UserEmailOwner
     */
    public function findByEmailAndOwner(Email $email, User $user)
    {
        return $this->findOneBy([
            'email' => $email,
            'owner' => $user,
        ]);
    }
}
