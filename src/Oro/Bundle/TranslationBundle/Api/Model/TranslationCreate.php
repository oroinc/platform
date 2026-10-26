<?php

namespace Oro\Bundle\TranslationBundle\Api\Model;

use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;

/**
 * Represents an object that is used to update translations by domain, key and language code.
 */
class TranslationCreate extends TranslationModel
{
    private string $domain;
    private string $key;
    private ?TranslationKey $newTranslationKey = null;

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

    public function setNewTranslationKey(TranslationKey $translationKey): void
    {
        $this->newTranslationKey = $translationKey;
    }

    public function getNewTranslationKey(): ?TranslationKey
    {
        return $this->newTranslationKey;
    }

    /**
     * Updates the ID of this model based on the ID of the new translation key, if one is set.
     * This method is needed because the new translation key receives its ID only after it is flushed to the database,
     * so the model's ID must be updated afterward.
     */
    public function synchronizeNewTranslationKeyId(): void
    {
        $keyId = $this->newTranslationKey?->getId();
        if (null !== $keyId) {
            $this->setId(TranslationIdUtil::buildTranslationId($keyId, $this->getLanguageCode()));
        }
    }
}
