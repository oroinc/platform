<?php

namespace Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

abstract class AbstractLoadSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $owner = $organization->getBusinessUnits()->first();
        foreach ($this->getSegmentsData() as $segmentReference => $data) {
            $entity = new Segment();
            $entity->setName($data['name']);
            $entity->setDescription($data['description']);
            $entity->setEntity($data['entity']);
            $entity->setOwner($owner);
            $entity->setType($manager->getRepository(SegmentType::class)->find($data['type']));
            $entity->setOrganization($organization);
            $entity->setDefinition(QueryDefinitionUtil::encodeDefinition($data['definition']));
            $this->setReference($segmentReference, $entity);
            $manager->persist($entity);
        }
        $manager->flush();
    }

    abstract protected function getSegmentsData(): array;
}
