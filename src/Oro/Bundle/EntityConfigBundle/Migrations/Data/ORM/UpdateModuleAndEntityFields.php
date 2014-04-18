<?php

namespace Oro\Bundle\EntityConfigBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;

class UpdateModuleAndEntityFields extends AbstractFixture
{
    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var EntityConfigModel[] $entities */
        $entities = $manager->getRepository('OroEntityConfigBundle:EntityConfigModel')->findAll();

        // update empty fields
        foreach ($entities as $entity) {
            $entity->setClassName($entity->getClassName());
        }

        $manager->flush();
    }
}
