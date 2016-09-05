<?php

namespace Oro\Bundle\DataGridBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\DataGridBundle\Entity\AppearanceType;

class LoadAppearanceTypes extends AbstractFixture
{
    /**
     * @var array
     */
    protected $data = [
        'grid'  => [
            'label' => 'oro.datagrid.appearance.grid',
            'icon'  => 'icon-table'
        ],
        'board'  => [
            'label' => 'oro.datagrid.appearance.board',
            'icon'  => 'icon-th'
        ],
    ];

    /**
     * @param ObjectManager $manager
     */
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
