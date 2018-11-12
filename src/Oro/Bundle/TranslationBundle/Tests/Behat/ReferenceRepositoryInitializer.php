<?php

namespace Oro\Bundle\TranslationBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

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
        $referenceRepository->set(
            'oro_entity_pagination_translation_key',
            $doctrine->getManager()->getRepository(TranslationKey::class)->findOneBy([
                'key' => 'oro.entity_pagination.pager_of_%total%_record|pager_of_%total%_records',
                'domain' => 'messages'
            ])
        );
    }
}
