<?php

namespace Oro\Bundle\TranslationBundle\Tests\Behat;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(ManagerRegistry $doctrine, Collection $referenceRepository): void
    {
        $referenceRepository->set(
            'en_language',
            $doctrine->getManager()->getRepository(Language::class)->findOneBy(['code' => 'en'])
        );
        $referenceRepository->set(
            'oro_entity_pagination_translation_key',
            $doctrine->getManager()->getRepository(TranslationKey::class)->findOneBy([
                'key' => 'oro.entity_pagination.pager_of_%count%_record|pager_of_%count%_records',
                'domain' => 'messages'
            ])
        );
    }
}
