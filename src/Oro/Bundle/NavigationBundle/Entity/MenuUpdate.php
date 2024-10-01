<?php

namespace Oro\Bundle\NavigationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Extend\Entity\Autocomplete\OroNavigationBundle_Entity_MenuUpdate;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\NavigationBundle\Entity\Repository\MenuUpdateRepository;

/**
 * Menu Update entity
 *
 *
 * @method MenuUpdate getTitle(Localization $localization = null)
 * @method MenuUpdate getDefaultTitle()
 * @method MenuUpdate setDefaultTitle($value)
 * @method MenuUpdate getDescription(Localization $localization = null)
 * @method MenuUpdate getDefaultDescription()
 * @method MenuUpdate setDefaultDescription($value)
 * @mixin OroNavigationBundle_Entity_MenuUpdate
 */
#[ORM\Entity(repositoryClass: MenuUpdateRepository::class)]
#[ORM\Table(name: 'oro_navigation_menu_upd')]
#[ORM\UniqueConstraint(name: 'oro_navigation_menu_upd_uidx', columns: ['key', 'scope_id', 'menu'])]
#[ORM\AssociationOverrides([
    new ORM\AssociationOverride(
        name: 'titles',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'menu_update_id',
            referencedColumnName: 'id',
            onDelete: 'CASCADE'
        )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'localized_value_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_navigation_menu_upd_title')
    ),
    new ORM\AssociationOverride(
        name: 'descriptions',
        joinColumns: [
        new ORM\JoinColumn(
            name: 'menu_update_id',
            referencedColumnName: 'id',
            onDelete: 'CASCADE'
        )
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(
                name: 'localized_value_id',
                referencedColumnName: 'id',
                unique: true,
                onDelete: 'CASCADE'
            )
        ],
        joinTable: new ORM\JoinTable(name: 'oro_navigation_menu_upd_descr')
    )
])]
#[ORM\HasLifecycleCallbacks]
#[Config(
    routeName: 'oro_navigation_global_menu_index',
    defaultValues: ['entity' => ['icon' => 'fa-th']]
)]
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

    #[\Override]
    public function getLinkAttributes(): array
    {
        return [];
    }
}
