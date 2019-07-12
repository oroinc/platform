<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
    {
        $referenceRepository->set(
            'adminEmailAddress',
            $doctrine->getManager()->getRepository(EmailAddressProxy::class)->findOneBy([])
        );
    }
}
