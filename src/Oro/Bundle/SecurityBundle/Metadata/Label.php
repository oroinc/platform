<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represents a translatable string.
 */
class Label
{
    /** @var string */
    protected $label;

    public function __construct(string $label)
    {
        $this->label = $label;
    }

    /**
     * Translates the label.
     *
     * @param TranslatorInterface $translator
     *
     * @return string
     */
    public function trans(TranslatorInterface $translator)
    {
        return $translator->trans($this->label);
    }

    public function __serialize(): array
    {
        return [$this->label];
    }

    public function __unserialize(array $serialized): void
    {
        [$this->label] = $serialized;
    }

    /**
     * @param array $data
     *
     * @return Label
     */
    // @codingStandardsIgnoreStart
    public static function __set_state($data)
    {
        return new Label($data['label']);
    }
    // @codingStandardsIgnoreEnd
}
