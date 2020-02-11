<?php

namespace Oro\Bundle\LocaleBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository): void
    {
        $localization = $doctrine->getManager()
            ->getRepository(Localization::class)
            ->findOneBy([]);

        if ($localization) {
            $referenceRepository->set('default_localization', $localization);
        }
    }
}
