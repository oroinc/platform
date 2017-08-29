<?php

namespace Oro\Bundle\TranslationBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        $referenceRepository->set(
            'en_language',
            $doctrine->getManager()->getRepository(Language::class)->findOneBy(['code' => 'en'])
        );
    }
}
