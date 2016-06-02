<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Suite\Suite;
use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Persister\Doctrine;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\EntityGuesser;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\EntitySupplement;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepository;
use Symfony\Component\Finder\Finder;

class FixtureLoader
{
    /**
     * @var Loader
     */
    protected $loader;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Doctrine
     */
    protected $persister;

    /**
     * @var string
     */
    protected $fallbackPath;

    /**
     * @var Suite
     */
    protected $suite;

    /**
     * @var EntityGuesser
     */
    protected $entityGuesser;

    /**
     * @var EntitySupplement
     */
    protected $entitySupplement;

    /**
     * @param Registry $registry
     * @param EntityGuesser $entityGuesser
     * @param EntitySupplement $entitySupplement
     */
    public function __construct(
        Registry $registry,
        EntityGuesser $entityGuesser,
        EntitySupplement $entitySupplement
    ) {
        $this->em = $registry->getManager();
        $this->persister = new Doctrine($this->em);
        $this->fallbackPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../Tests/Behat');
        $this->loader = new Loader();
        $this->entityGuesser = $entityGuesser;
        $this->entitySupplement = $entitySupplement;
    }

    /**
     * @param string $filename
     * @throws \InvalidArgumentException
     */
    public function loadFixtureFile($filename)
    {
        $finder = new Finder();
        $paths = array_merge($this->suite->getSetting('paths'), [$this->fallbackPath]);
        $finder->in($paths)->name($filename);

        if (!$finder->count()) {
            throw new \InvalidArgumentException(sprintf(
                'Can\'t find "%s" in pahts %s',
                $filename,
                implode(', ', $paths)
            ));
        }

        $file = $finder->getIterator()->current()->getRealpath();

        $objects = $this->loader->load($file);
        $this->persister->persist($objects);
    }

    /**
     * @param string $name
     * @param TableNode $table
     */
    public function loadTable($name, TableNode $table)
    {
        $className = $this->entityGuesser->guessEntityClass($name);

        $rows = $table->getRows();
        $headers = array_shift($rows);

        foreach ($rows as $row) {
            $values = array_combine($headers, $row);
            $aliceFixture = $this->buildAliceFixture($className, $values);
            $objects = $this->loader->load($aliceFixture);

            $object = array_shift($objects);
            $this->entitySupplement->completeRequired($object);

            $this->em->persist($object);
        }

        $this->em->flush();
    }

    /**
     * @param string $name
     * @param integer $nbr
     */
    public function loadRandomEntities($name, $nbr)
    {
        $className = $this->entityGuesser->guessEntityClass($name);

        for ($i = 0; $i < $nbr; $i++) {
            $object = new $className;
            $this->entitySupplement->completeRequired($object);

            $this->em->persist($object);
        }

        $this->em->flush();
    }

    /**
     * @param ReferenceRepository $referenceRepository
     */
    public function initReferences(ReferenceRepository $referenceRepository)
    {
        $this->loader->setReferences($referenceRepository->references);
    }

    public function clearReferences()
    {
        $this->loader->setReferences([]);
    }

    /**
     * @param Suite $suite
     */
    public function setSuite(Suite $suite)
    {
        $this->suite = $suite;
    }

    /**
     * @param string $className FQCN
     * @param array $values
     * @return array
     */
    protected function buildAliceFixture($className, array $values)
    {
        $entityReference = uniqid('', true);

        return [
            $className => [
                $entityReference => $values
        ]];
    }
}
