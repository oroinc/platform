<?php

namespace Oro\Bundle\AddressBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\LocaleBundle\Entity\AbstractTranslation;

/**
 * Translation entity for AddressType entity.
 */
#[ORM\Entity]
#[ORM\Table(name: 'oro_address_type_translation')]
#[ORM\Index(columns: ['locale', 'object_class', 'field', 'foreign_key'], name: 'address_type_translation_idx')]
class AddressTypeTranslation extends AbstractTranslation
{
    /**
     * @var string|null
     */
    #[ORM\Column(name: 'foreign_key', type: Types::STRING, length: 16)]
    protected $foreignKey;
}
