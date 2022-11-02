<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Translation entity for AddressType entity.
 *
 * @ORM\Table(name="oro_address_type_translation", indexes={
 *      @ORM\Index(name="address_type_translation_idx", columns={"locale", "object_class", "field", "foreign_key"})
 * })
 * @ORM\Entity()
 */
class AddressTypeTranslation extends AbstractTranslation
{
    /**
     * @var string $foreignKey
     *
     * @ORM\Column(name="foreign_key", type="string", length=16)
     */
    protected $foreignKey;
}
