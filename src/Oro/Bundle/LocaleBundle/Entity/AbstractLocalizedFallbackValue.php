<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\LocaleBundle\Model\FallbackType;

/**
 * Abstract class for all localized value entities
 *
 * @ORM\MappedSuperclass()
 */
abstract class AbstractLocalizedFallbackValue
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="fallback", type="string", length=64, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
     */
    protected $fallback;

    /**
     * @var string|null
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
     */
    protected $string;

    /**
     * @var string|null
     *
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
     */
    protected $text;

    /**
     * @var Localization|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\LocaleBundle\Entity\Localization")
     * @ORM\JoinColumn(name="localization_id", referencedColumnName="id", onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $localization;

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
