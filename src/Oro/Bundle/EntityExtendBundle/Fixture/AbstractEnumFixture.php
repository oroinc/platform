<?php

namespace Oro\Bundle\EntityExtendBundle\Fixture;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;

abstract class AbstractEnumFixture extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository($this->getClassName());

        $priority = 1;
        foreach ($this->getData() as $id => $name) {
            $isDefault = $id === $this->getDefaultValue();
            $enumOption = $enumRepo->createEnumValue($name, $priority++, $isDefault, $id);
            $manager->persist($enumOption);
        }

        $manager->flush();
    }

    /**
     * Returns an id of a default enum value
     *
     * @return string|null
     */
    protected function getDefaultValue()
    {
        return null;
    }

    /**
     * Returns an array of possible enum values, where array key is an id and array value is an English translation
     *
     * @return array
     */
    abstract protected function getData();

    /**
     * Returns a class name of an extend entity
     *
     * @return string
     */
    abstract protected function getClassName();
}
