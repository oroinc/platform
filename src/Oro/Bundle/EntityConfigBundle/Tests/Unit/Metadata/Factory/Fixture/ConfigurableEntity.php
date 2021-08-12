<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\Entity
 * @Config(
 *      defaultValues={
 *          "scope"={
 *              "key"="value"
 *          }
 *      }
 * )
 */
class ConfigurableEntity extends ParentClass
{
    /**
     * @var int
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="value"
     *          }
     *      }
     * )
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="value"
     *          }
     *      }
     * )
     */
    private $name;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="value"
     *          }
     *      }
     * )
     */
    private $privateFieldWithConfigInParent;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $fieldWithConfigInParent1;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="value"
     *          }
     *      }
     * )
     */
    protected $fieldWithConfigInParent2;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="value"
     *          }
     *      }
     * )
     */
    protected $fieldWithoutConfigInParent1;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $fieldWithoutConfigInParent2;
}
