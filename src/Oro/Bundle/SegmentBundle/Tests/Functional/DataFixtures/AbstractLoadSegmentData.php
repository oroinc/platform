<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

abstract class AbstractLoadSegmentData extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $owner = $organization->getBusinessUnits()->first();

        foreach ($this->getSegmentsData() as $segmentReference => $data) {
            $segmentType = $manager->getRepository(SegmentType::class)->find($data['type']);

            $entity = new Segment();
            $entity->setName($data['name']);
            $entity->setDescription($data['description']);
            $entity->setEntity($data['entity']);
            $entity->setOwner($owner);
            $entity->setType($segmentType);
            $entity->setOrganization($organization);
            $entity->setDefinition(json_encode($data['definition']));

            $this->setReference($segmentReference, $entity);

            $manager->persist($entity);
        }

        $manager->flush();
    }

    /**
     * @return array
     */
    abstract protected function getSegmentsData(): array;
}
