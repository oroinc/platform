<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Nelmio\Alice\Persister\Doctrine as AliceDoctrine;
use Symfony\Bridge\Doctrine\RegistryInterface;

class OroAliceLoader extends Loader
{
    public function __construct($locale = 'en_US', array $providers = [], $seed = 1, array $parameters = [])
    {
        parent::__construct($locale, $providers, $seed, $parameters);
    }

    /**
     * @return AliceCollection
     */
    public function getReferenceRepository()
    {
        return $this->objects;
    }

    public function setDoctrine(RegistryInterface $doctrine)
    {
        $this->typeHintChecker->setPersister(new AliceDoctrine($doctrine->getManager()));
    }
}
