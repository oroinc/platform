<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $referenceRepository->set(
            'adminEmailAddress',
            $doctrine->getManager()->getRepository(EmailAddressProxy::class)->findOneBy([])
        );
    }
}
