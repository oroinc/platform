<?php

namespace Oro\Bundle\TranslationBundle\Api\Model;

/**
 * The base class for objects that are used to update translations.
 */
abstract class TranslationModel
{
    private string $id;
    private int $translationKeyId;
    private string $languageCode;
    private ?int $translationEntityId;
    private ?string $translatedValue;
    private array $attributes = [];

    public function __construct(
        string $id,
        int $translationKeyId,
        string $languageCode,
        ?int $translationEntityId = null,
        ?string $translatedValue = null
    ) {
        $this->id = $id;
        $this->translationKeyId = $translationKeyId;
        $this->languageCode = $languageCode;
        $this->translationEntityId = $translationEntityId;
        $this->translatedValue = $translatedValue;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTranslationKeyId(): int
    {
        return $this->translationKeyId;
    }

    public function getLanguageCode(): string
    {
        return $this->languageCode;
    }

    public function getTranslationEntityId(): ?int
    {
        return $this->translationEntityId;
    }

    public function getTranslatedValue(): ?string
    {
        return $this->translatedValue;
    }

    public function setTranslatedValue(?string $translatedValue): void
    {
        $this->translatedValue = $translatedValue;
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function __isset(string $name): bool
    {
        return true;
    }
}
