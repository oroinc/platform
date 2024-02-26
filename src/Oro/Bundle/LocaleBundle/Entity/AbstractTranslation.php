<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation as GedmoAbstractTranslation;

/**
 * Abstract class to host common translated entity properties
 */
abstract class AbstractTranslation extends GedmoAbstractTranslation
{
    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 16)]
    protected $locale;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    protected $content;
}
