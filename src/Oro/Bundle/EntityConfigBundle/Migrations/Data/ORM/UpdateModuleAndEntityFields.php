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
        /** @var EntityRepository $repository */
        $repository = $manager->getRepository('OroEntityConfigBundle:EntityConfigModel');
        /** @var EntityConfigModel[] $outdatedEntities */
        $outdatedEntities = $repository->createQueryBuilder('entity')
            ->where('entity.moduleName = :empty')
            ->orWhere('entity.entityName = :empty')
            ->setParameter('empty', '')
            ->getQuery()
            ->execute();

        if (empty($outdatedEntities)) {
            return;
        }

        // update empty fields
        foreach ($outdatedEntities as $entity) {
            $entity->setClassName($entity->getClassName());
        }

        $manager->flush();
    }
}
