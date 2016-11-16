<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\NavigationBundle\Model\ExtendMenuUpdate;

/**
 * @ORM\Entity
 * @ORM\Table(
 *      name="oro_navigation_menu_upd",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_navigation_menu_upd_uidx",
 *              columns={"key", "scope_id"}
 *          )
 *      }
 * )
 * @ORM\AssociationOverrides({
 *      @ORM\AssociationOverride(
 *          name="titles",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_navigation_menu_upd_title",
 *              joinColumns={
 *                  @ORM\JoinColumn(
 *                      name="menu_update_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      ),
 *      @ORM\AssociationOverride(
 *          name="descriptions",
 *          joinTable=@ORM\JoinTable(
 *              name="oro_navigation_menu_upd_descr",
 *              joinColumns={
 *                  @ORM\JoinColumn(
 *                      name="menu_update_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE"
 *                  )
 *              },
 *              inverseJoinColumns={
 *                  @ORM\JoinColumn(
 *                      name="localized_value_id",
 *                      referencedColumnName="id",
 *                      onDelete="CASCADE",
 *                      unique=true
 *                  )
 *              }
 *          )
 *      )
 * })
 * @Config(
 *      routeName="oro_navigation_menu_update_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-th"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class MenuUpdate extends ExtendMenuUpdate implements
    MenuUpdateInterface
{
    use MenuUpdateTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->titles = new ArrayCollection();
        $this->descriptions = new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtras()
    {
        $extras = [
            'divider' => $this->isDivider(),
            'translate_disabled' => $this->getId() ? true : false
        ];

        if ($this->getPriority() !== null) {
            $extras['position'] = $this->getPriority();
        }

        if ($this->getIcon() !== null) {
            $extras['icon'] = $this->getIcon();
        }

        return $extras;
    }
}
