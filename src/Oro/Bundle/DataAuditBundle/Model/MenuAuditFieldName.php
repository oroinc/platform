<?php

namespace Oro\Bundle\DataAuditBundle\Model;

/**
 * The name a changed property of a menu item is recorded under: the property itself, plus the localization
 * its value belongs to when the property is localized.
 */
final class MenuAuditFieldName implements \Stringable
{
    private const string SEPARATOR = '|';

    private readonly ?string $localization;

    public function __construct(
        private readonly string $property,
        ?string $localization = null
    ) {
        $this->localization = '' === $localization ? null : $localization;
    }

    public static function parse(string $field): self
    {
        [$property, $localization] = array_pad(explode(self::SEPARATOR, $field, 2), 2, null);

        return new self($property, $localization);
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getLocalization(): ?string
    {
        return $this->localization;
    }

    #[\Override]
    public function __toString(): string
    {
        return null === $this->localization
            ? $this->property
            : $this->property . self::SEPARATOR . $this->localization;
    }
}
