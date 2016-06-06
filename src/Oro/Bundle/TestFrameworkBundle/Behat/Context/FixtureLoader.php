<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Suite\Suite;
use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Persister\Doctrine;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\EntityClassResolver;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\EntitySupplement;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\ReferenceRepository;

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
     * @var EntityClassResolver
     */
    protected $entityClassResolver;

    /**
     * @var EntitySupplement
     */
    protected $entitySupplement;

    /**
     * @param Registry $registry
     * @param EntityClassResolver $entityClassResolver
     * @param EntitySupplement $entitySupplement
     */
    public function __construct(
        Registry $registry,
        EntityClassResolver $entityClassResolver,
        EntitySupplement $entitySupplement
    ) {
        $this->em = $registry->getManager();
        $this->persister = new Doctrine($this->em);
        $this->fallbackPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../Tests/Behat');
        $this->loader = new Loader();
        $this->entityClassResolver = $entityClassResolver;
        $this->entitySupplement = $entitySupplement;
    }

    /**
     * @param string $filename
     * @throws \InvalidArgumentException
     */
    public function loadFixtureFile($filename)
    {
        $file = $this->findFile($filename);

        $objects = $this->loader->load($file);
        $this->persister->persist($objects);
    }

    /**
     * @param string $entityName
     * @param TableNode $table
     */
    public function loadTable($entityName, TableNode $table)
    {
        $className = $this->entityClassResolver->getEntityClass($entityName);

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
     * @param string $entityName
     * @param integer $numberOfEntities
     */
    public function loadRandomEntities($entityName, $numberOfEntities)
    {
        $className = $this->entityClassResolver->getEntityClass($entityName);

        for ($i = 0; $i < $numberOfEntities; $i++) {
            $entity = new $className;
            $this->entitySupplement->completeRequired($entity);

            $this->em->persist($entity);
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
            ]
        ];
    }

    /**
     * @param string $filename
     * @return string Real path to file with fuxtures
     * @throws \InvalidArgumentException
     */
    protected function findFile($filename)
    {
        $suitePaths = $this->suite->getSetting('paths');

        if (!$file = $this->findFileInPath($filename, $suitePaths)) {
            $file = $this->findFileInPath($filename, [$this->fallbackPath]);
        }

        if (!$file) {
            throw new \InvalidArgumentException(sprintf(
                'Can\'t find "%s" in pahts %s',
                $filename,
                implode(', ', array_merge($suitePaths, [$this->fallbackPath]))
            ));
        }

        return $file;
    }

    /**
     * @param string $filename
     * @param array $paths
     * @return string|null
     */
    private function findFileInPath($filename, array $paths)
    {
        foreach ($paths as $path) {
            $file = $path.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$filename;
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}
