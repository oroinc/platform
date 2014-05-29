<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadSegmentData extends AbstractFixture
{
    const COUNT = 50;
    public function load(ObjectManager $manager)
    {
        $staticType = $manager->getRepository('OroSegmentBundle:SegmentType')->find(SegmentType::TYPE_STATIC);

        if (!$staticType) {
            $staticType  = new SegmentType(SegmentType::TYPE_STATIC);
            $staticType->setLabel('Static');
            $manager->persist($staticType);
        }

        $dynamicType = $manager->getRepository('OroSegmentBundle:SegmentType')->find(SegmentType::TYPE_DYNAMIC);
        if (!$dynamicType) {
            $dynamicType = new SegmentType(SegmentType::TYPE_DYNAMIC);
            $dynamicType->setLabel('Dynamic');
            $manager->persist($dynamicType);
        }

        $organisation = $manager->getRepository('OroOrganizationBundle:Organization')
            ->findOneBy(array('name' => 'Test Org'));

        if (!$organisation) {
            $organisation = new Organization();
            $organisation->setName('Test Org');
            $organisation->setCurrency('USD');
            $organisation->setPrecision('0.00');
            $manager->persist($organisation);
        }

        $owner = $manager->getRepository('OroOrganizationBundle:BusinessUnit')->findOneBy(array('name' => 'Test'));
        if (!$owner) {
            $owner = new BusinessUnit();
            $owner->setName('Test');
            $owner->setOrganization($organisation);
            $manager->persist($owner);
        }

        for ($i = 1; $i <= self::COUNT; $i++) {
            $definition = array(
                'columns' => array(
                    'func'    => null,
                    'label'   => 'label' . $i,
                    'name'    => '',
                    'sorting' => ''
                ),
                'filters' => array()
            );

            $entity = new Segment();
            $entity->setCreatedAt(new \DateTime('now'));
            $entity->setDefinition(json_encode($definition));
            $entity->setDescription('description_' . $i);
            $entity->setEntity('Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity');
            $entity->setLastRun(new \DateTime('now'));
            $entity->setName('segment_' . $i);
            $entity->setOwner($owner);
            $entity->setType((rand(0, 100) % 2) ? $staticType : $dynamicType);
            $entity->setUpdatedAt(new \DateTime('now'));
            $manager->persist($entity);
        }

        $manager->flush();
    }
}
