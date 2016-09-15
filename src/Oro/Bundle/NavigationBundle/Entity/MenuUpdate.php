<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Model\ExtendMenuUpdate;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository")
 * @ORM\Table(name="oro_navigation_menu_upd")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-th"
 *          }
 *      }
 * )
 */
class MenuUpdate extends ExtendMenuUpdate implements
    MenuUpdateInterface
{
    use MenuUpdateTrait;
    
    const OWNERSHIP_USER            = 3;

    /**
     * @var Collection|LocalizedFallbackValue[]
     *
     * @ORM\ManyToMany(
     *      targetEntity="Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue",
     *      cascade={"ALL"},
     *      orphanRemoval=true
     * )
     * @ORM\JoinTable(
     *      name="oro_navigation_menu_upd_title",
     *      joinColumns={
     *          @ORM\JoinColumn(name="menu_update_id", referencedColumnName="id", onDelete="CASCADE")
     *      },
     *      inverseJoinColumns={
     *          @ORM\JoinColumn(name="localized_value_id", referencedColumnName="id", onDelete="CASCADE", unique=true)
     *      }
     * )
     */
    protected $titles;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        $extras = [];

        if ($this->getPriority() !== null) {
            $extras['position'] = $this->getPriority();
        }

        return $extras;
    }
}
