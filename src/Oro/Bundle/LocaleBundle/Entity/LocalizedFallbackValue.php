<?php

namespace Oro\Bundle\LocaleBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * Standard entity to store string data related to the some localization.
 *
 * @ORM\Table(
 *      name="oro_fallback_localization_val",
 *      indexes={
 *          @ORM\Index(name="idx_fallback", columns={"fallback"}),
 *          @ORM\Index(name="idx_string", columns={"string"})
 *      }
 * )
 * @ORM\Entity(repositoryClass="Oro\Bundle\LocaleBundle\Entity\Repository\LocalizedFallbackValueRepository")
 * @Config(
 *      defaultValues={
 *          "dataaudit"={
 *              "auditable"=true
 *          }
 *      }
 * )
 */
class LocalizedFallbackValue extends AbstractLocalizedFallbackValue implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var string|null
     *
     * @ORM\Column(name="string", type="string", length=255, nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
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
     * @ORM\Column(name="text", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "excluded"=false
     *          }
     *      }
     * )
     */
    protected $text;
}
