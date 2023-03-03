<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;

/**
 * Menu Update entity
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository")
 * @ORM\Table(
 *      name="oro_navigation_menu_upd",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_navigation_menu_upd_uidx",
 *              columns={"key", "scope_id", "menu"}
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
 *      routeName="oro_navigation_global_menu_index",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-th"
 *          }
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 *
 * @method MenuUpdate getTitle(Localization $localization = null)
 * @method MenuUpdate getDefaultTitle()
 * @method MenuUpdate setDefaultTitle($value)
 * @method MenuUpdate getDescription(Localization $localization = null)
 * @method MenuUpdate getDefaultDescription()
 * @method MenuUpdate setDefaultDescription($value)
 */
class MenuUpdate implements
    MenuUpdateInterface,
    ExtendEntityInterface
{
    use MenuUpdateTrait {
        MenuUpdateTrait::__construct as traitConstructor;
    }
    use ExtendEntityTrait;

    public function __construct()
    {
        $this->traitConstructor();
    }

    public function getLinkAttributes(): array
    {
        return [];
    }
}
