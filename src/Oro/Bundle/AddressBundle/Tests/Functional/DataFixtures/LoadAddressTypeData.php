<?php

namespace Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AddressType;

class LoadAddressTypeData extends AbstractFixture
{
    const TYPE_HOME = 'home';
    const TYPE_WORK = 'work';
    const TYPE_SECRET = 'secret';

    /**
     * @var array
     */
    protected $addressTypes = [
        ['name' => self::TYPE_HOME, 'label' => 'Home'],
        ['name' => self::TYPE_WORK, 'label' => 'Work'],
        ['name' => self::TYPE_SECRET, 'label' => 'Secret']
    ];

    /**
     * Load address types
     *
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->addressTypes as $addressTypeInfo) {
            $addressType = new AddressType($addressTypeInfo['name']);
            $addressType
                ->setLabel($addressTypeInfo['label']);

            $this->setReference($addressType->getName(), $addressType);

            $manager->persist($addressType);
        }

        $manager->flush();
    }
}
