<?php

namespace Oro\Bundle\TestFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @ORM\Table(name="test_search_item")
 * @ORM\Entity
 * @Config(
 *      routeName="oro_test_item_index",
 *      routeView="oro_test_item_view",
 *      routeCreate="oro_test_item_create",
 *      routeUpdate="oro_test_item_update"
 * )
 */
class Item implements TestFrameworkEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $stringValue
     *
     * @ORM\Column(name="stringValue", type="string", nullable=true)
     */
    protected $stringValue;

    /**
     * @var integer $integerValue
     *
     * @ORM\Column(name="integerValue", type="integer", nullable=true)
     */
    protected $integerValue;

    /**
     * @var float $decimalValue
     *
     * @ORM\Column(name="decimalValue", type="decimal", scale=2, nullable=true)
     */
    protected $decimalValue;

    /**
     * @var float $floatValue
     *
     * @ORM\Column(name="floatValue", type="float", nullable=true)
     */
    protected $floatValue;

    /**
     * @var boolean $booleanValue
     *
     * @ORM\Column(name="booleanValue", type="boolean", nullable=true)
     */
    protected $booleanValue;

    /**
     * @var string $blobValue
     *
     * @ORM\Column(name="blobValue", type="blob", nullable=true)
     */
    protected $blobValue;

    /**
     * @var array $arrayValue
     *
     * @ORM\Column(name="arrayValue", type="array", nullable=true)
     */
    protected $arrayValue;

    /**
     * @var \DateTime $arrayValue
     *
     * @ORM\Column(name="datetimeValue", type="datetime", nullable=true)
     */
    protected $datetimeValue;

    /**
     * @var string $arrayValue
     *
     * @ORM\Column(name="guidValue", type="guid", nullable=true)
     */
    protected $guidValue;

    /**
     * @var object $objectValue
     *
     * @ORM\Column(name="objectValue", type="object", nullable=true)
     */
    protected $objectValue;

    /**
     * @var ItemValue[]
     *
     * @ORM\OneToMany(targetEntity="ItemValue", mappedBy="entity", cascade={"persist", "remove"})
     */
    protected $values;

    /**
     * @var string
     *
     * @ORM\Column(name="phone1", type="string", nullable=true)
     */
    protected $phone;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $owner;

    /**
     * @var Organization
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\Organization")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $organization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __toString()
    {
        return (string) $this->stringValue;
    }
}
