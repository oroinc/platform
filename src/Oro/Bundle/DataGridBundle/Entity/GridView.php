<?php

namespace Oro\Bundle\DataGridBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\DataGridBundle\Entity\Repository\GridViewRepository")
 * @ORM\Table(name="oro_grid_view")
 * @Config(
 *      defaultValues={
 *          "ownership"={
 *              "owner_type"="USER",
 *              "owner_field_name"="owner",
 *              "owner_column_name"="user_owner_id"
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *          }
 *      }
 * )
 */
class GridView
{
    const TYPE_PRIVATE = 'private';
    const TYPE_PUBLIC = 'public';

    /**
     * @var array
     */
    protected static $types = [
        self::TYPE_PRIVATE => self::TYPE_PRIVATE,
        self::TYPE_PUBLIC => self::TYPE_PUBLIC,
    ];

    /**
     * @var int $id
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @Assert\Choice(callback={"Oro\Bundle\DataGridBundle\Entity\GridView", "getTypes"})
     */
    protected $type = self::TYPE_PRIVATE;

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $filtersData = [];

    /**
     * @var array
     *
     * @ORM\Column(type="array")
     */
    protected $sortersData = [];

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     */
    protected $gridName;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", onDelete="CASCADE")
     * @Assert\NotBlank
     */
    protected $owner;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getFiltersData()
    {
        return $this->filtersData;
    }

    /**
     * @return array
     */
    public function getSortersData()
    {
        return $this->sortersData;
    }

    /**
     * @return string
     */
    public function getGridName()
    {
        return $this->gridName;
    }

    /**
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @param int $id
     *
     * @return this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param array $filtersData
     *
     * @return this
     */
    public function setFiltersData(array $filtersData = [])
    {
        $this->filtersData = $filtersData;

        return $this;
    }

    /**
     * @param array $sortersData
     *
     * @return this
     */
    public function setSortersData(array $sortersData = [])
    {
        $this->sortersData = $sortersData;

        return $this;
    }

    /**
     * @param string $gridName
     *
     * @return this
     */
    public function setGridName($gridName)
    {
        $this->gridName = $gridName;

        return $this;
    }

    /**
     * @param User $owner
     *
     * @return this
     */
    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;

        return $this;
    }

    /**
     * @return View
     */
    public function createView()
    {
        return new View($this->id, $this->filtersData, $this->sortersData, $this->type);
    }

    /**
     * @return array
     */
    public static function getTypes()
    {
        return static::$types;
    }
}
