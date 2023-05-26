<?php

namespace Oro\Bundle\TranslationBundle\Api\Model;

/**
 * Represents an object that is used to update translations by domain, key and language code.
 */
class TranslationCreate extends TranslationModel
{
    private string $domain;
    private string $key;

    public function __construct(
        string $domain,
        string $key,
        int $translationKeyId,
        string $languageCode,
        ?int $translationEntityId = null,
        ?string $translatedValue = null
    ) {
        $this->domain = $domain;
        $this->key = $key;
        parent::__construct('', $translationKeyId, $languageCode, $translationEntityId, $translatedValue);
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}
