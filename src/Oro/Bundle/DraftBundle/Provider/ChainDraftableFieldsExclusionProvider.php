<?php

namespace Oro\Bundle\DraftBundle\Provider;

/**
 * Delegates the getting of draftable fields that should be excluded from the confirmation message to child providers.
 */
class ChainDraftableFieldsExclusionProvider
{
    /** @var iterable|DraftableFieldsExclusionProviderInterface[] */
    private $providers;

    /**
     * @param iterable|DraftableFieldsExclusionProviderInterface[] $providers
     */
    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getExcludedFields(string $className): array
    {
        $excludedFields = [];
        foreach ($this->providers as $provider) {
            if ($provider->isSupport($className)) {
                $fields = $provider->getExcludedFields();
                if ($fields) {
                    $excludedFields[] = $fields;
                }
            }
        }
        if ($excludedFields) {
            $excludedFields = \array_unique(\array_merge(...$excludedFields));
        }

        return $excludedFields;
    }
}
