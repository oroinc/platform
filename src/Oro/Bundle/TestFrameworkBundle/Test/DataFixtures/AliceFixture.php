<?php

namespace Oro\Bundle\TestFrameworkBundle\Test\DataFixtures;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\DataFixtures\SharedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

abstract class AliceFixture implements
    SharedFixtureInterface,
    AliceFixtureLoaderAwareInterface
{
    /** @var ReferenceRepository */
    protected $referenceRepository;

    /** @var AliceFixtureLoader */
    protected $loader;

    /**
     * @return array
     */
    abstract protected function loadData();

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $aliceReferenceRepository = $this->loader->getReferenceRepository();
        $referenceRepository = $this->referenceRepository;

        $references = array_keys($referenceRepository->getReferences());
        foreach ($references as $name) {
            if (!$aliceReferenceRepository->containsKey($name)) {
                /** Used gerReference method for every reference to make sure that it's not detached */
                $object = $referenceRepository->getReference($name);
                $aliceReferenceRepository->set($name, $object);
            }
        }
        unset($references);

        $loaderObjects = $this->loadData();
        foreach ($loaderObjects as $object) {
            $manager->persist($object);
        }
        $manager->flush();

        $references = $aliceReferenceRepository->toArray();
        foreach ($references as $name => $object) {
            if (!$referenceRepository->hasReference($name)) {
                $referenceRepository->setReference($name, $object);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setReferenceRepository(ReferenceRepository $referenceRepository)
    {
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function setLoader(AliceFixtureLoader $loader)
    {
        $this->loader = $loader;
    }
}
