<?php

namespace Oro\Bundle\EmailBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use OroEntityProxy\OroEmailBundle\EmailAddressProxy;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $referenceRepository->set(
            'adminEmailAddress',
            $doctrine->getManager()->getRepository(EmailAddressProxy::class)->findOneBy([])
        );
    }
}
