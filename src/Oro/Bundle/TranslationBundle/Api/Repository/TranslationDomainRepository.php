<?php

namespace Oro\Bundle\TranslationBundle\Api\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Api\Model\TranslationDomain;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainDescriptionProviderInterface;

/**
 * The repository to get available translation domains.
 */
class TranslationDomainRepository
{
    private ManagerRegistry $doctrine;
    private TranslationDomainDescriptionProviderInterface $descriptionProvider;

    public function __construct(
        ManagerRegistry $doctrine,
        TranslationDomainDescriptionProviderInterface $descriptionProvider
    ) {
        $this->doctrine = $doctrine;
        $this->descriptionProvider = $descriptionProvider;
    }

    /**
     * Returns all available translation domains.
     *
     * @return TranslationDomain[]
     */
    public function getTranslationDomains(): array
    {
        $domains = [];
        $names = $this->doctrine->getRepository(TranslationKey::class)->findAvailableDomains();
        foreach ($names as $name) {
            $domains[] = new TranslationDomain(
                $name,
                $this->descriptionProvider->getTranslationDomainDescription($name)
            );
        }

        return $domains;
    }
}
