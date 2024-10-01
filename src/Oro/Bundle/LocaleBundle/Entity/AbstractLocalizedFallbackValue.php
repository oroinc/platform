<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

/**
 * Abstract class for all localized value entities
 */
#[ORM\MappedSuperclass]
abstract class AbstractLocalizedFallbackValue
{
    #[ORM\Column(name: 'id', type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => true]])]
    protected ?int $id = null;

    #[ORM\Column(name: 'fallback', type: Types::STRING, length: 64, nullable: true)]
    #[ConfigField(defaultValues: ['importexport' => ['excluded' => false]])]
    protected ?string $fallback = null;

    #[ConfigField(defaultValues: ['importexport' => ['excluded' => false]])]
    protected ?string $string = null;

    #[ConfigField(defaultValues: ['importexport' => ['excluded' => false]])]
    protected ?string $text = null;

    #[ORM\ManyToOne(targetEntity: Localization::class)]
    #[ORM\JoinColumn(name: 'localization_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?Localization $localization = null;

    /**
     * @return array
     */
    public static function getFallbacks()
    {
        return [FallbackType::SYSTEM, FallbackType::PARENT_LOCALIZATION, FallbackType::NONE];
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $fallback
     * @return $this
     */
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }

    /**
     * @return string
     */
    public function getFallback()
    {
        return $this->fallback;
    }

    /**
     * @param string $string
     * @return $this
     */
    public function setString($string)
    {
        $this->string = $string;

        return $this;
    }

    /**
     * @return string
     */
    public function getString()
    {
        return $this->string;
    }

    /**
     * @param string $text
     * @return $this
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @return Localization|null
     */
    public function getLocalization()
    {
        return $this->localization;
    }

    /**
     * @param Localization|null $localization
     * @return $this
     */
    public function setLocalization(Localization $localization = null)
    {
        $this->localization = $localization;

        return $this;
    }

    /**
     * @return string
     */
    #[\Override]
    public function __toString()
    {
        $fields = get_object_vars($this);
        if (is_subclass_of($this::class, ExtendEntityInterface::class)) {
            $fields = array_merge($fields, $this->getExtendStorageFields());
        }
        ksort($fields);

        foreach ($fields as $field => $value) {
            if ($value && is_string($value) && !in_array($field, ['id', 'fallback'], true)) {
                return $value;
            }
        }

        return '';
    }

    public function __clone()
    {
        $this->id = null;
    }

    public static function createFromAbstract(AbstractLocalizedFallbackValue $value): AbstractLocalizedFallbackValue
    {
        $object = new static();
        $object->setLocalization($value->getLocalization());
        $object->setFallback($value->getFallback());
        $object->setString($value->getString());
        $object->setText($value->getText());

        return $object;
    }
}
