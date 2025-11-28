<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository;

/**
 * An entity to store string or text value for some localization.
 *
 * @mixin \Extend\Entity\Autocomplete\OroLocaleBundle_Entity_LocalizedFallbackValue
 */
#[ORM\Entity(repositoryClass: LocalizedFallbackValueRepository::class)]
#[ORM\Table(name: 'oro_fallback_localization_val')]
#[ORM\Index(columns: ['fallback'], name: 'idx_fallback')]
#[ORM\Index(columns: ['string'], name: 'idx_string')]
#[Config(defaultValues: ['dataaudit' => ['auditable' => true]])]
class LocalizedFallbackValue extends AbstractLocalizedFallbackValue implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'string', type: Types::STRING, length: 255, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => false]])]
    protected ?string $string = null;

    #[ORM\Column(name: 'text', type: Types::TEXT, nullable: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['excluded' => false]])]
    protected ?string $text = null;

    public static function createString(string $value, ?Localization $localization = null): self
    {
        $result = (new static())->setString($value);
        if (null !== $localization) {
            $result->setLocalization($localization);
        }
        return $result;
    }
}
