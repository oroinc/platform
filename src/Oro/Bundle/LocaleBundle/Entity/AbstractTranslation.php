<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Entity\MappedSuperclass\AbstractTranslation as GedmoAbstractTranslation;

/**
 * Abstract class to host common translated entity properties
 */
abstract class AbstractTranslation extends GedmoAbstractTranslation
{
    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     */
    protected $locale;

    /**
     * @var string $content
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $content;
}
