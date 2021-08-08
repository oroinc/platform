<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Factory\Fixture;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

/**
 * @ORM\MappedSuperclass
 * @Config(
 *      defaultValues={
 *          "scope"={
 *              "key"="parentValue"
 *          }
 *      }
 * )
 */
class ParentEntity
{
    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      mode="readonly",
     *      defaultValues={
     *          "scope"={
     *              "key"="parentValue"
     *          }
     *      }
     * )
     */
    private $parentName;

    /**
     * @var string
     */
    private $parentLabel;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="parentValue"
     *          }
     *      }
     * )
     */
    private $privateFieldWithConfigInParent;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "scope"={
     *              "key"="parentValue"
     *          }
     *      }
     * )
     */
    protected $fieldWithConfigInParent1;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "email"={
     *              "available_in_template"=false
     *          }
     *      }
     * )
     */
    protected $fieldWithConfigInParent2;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $fieldWithoutConfigInParent1;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $fieldWithoutConfigInParent2;
}
