<?php

namespace Oro\Bundle\SecurityBundle\Metadata;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Represents a translatable string.
 */
class Label implements \Serializable
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

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize($this->label);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $this->label = unserialize($serialized);
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
