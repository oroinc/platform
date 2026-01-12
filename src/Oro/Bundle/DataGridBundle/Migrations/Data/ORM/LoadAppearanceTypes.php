<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataGridBundle\Entity\AppearanceType;

/**
 * Loads default datagrid appearance types into the database.
 *
 * This data fixture creates the standard appearance types (grid and board) that control
 * how datagrids can be displayed in the application.
 */
class LoadAppearanceTypes extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        'grid'  => [
            'label' => 'oro.datagrid.appearance.grid',
            'icon'  => 'fa-table'
        ],
        'board'  => [
            'label' => 'oro.datagrid.appearance.board',
            'icon'  => 'fa-th'
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager)
    {
        foreach ($this->data as $name => $typeData) {
            $type = new AppearanceType($name);
            $type->setLabel($typeData['label']);
            $type->setIcon($typeData['icon']);
            $manager->persist($type);
        }

        $manager->flush();
    }
}
