<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Suite\Suite;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader as AliceLoader;
use Nelmio\Alice\Persister\Doctrine as AliceDoctrine;
use Oro\Bundle\EntityBundle\ORM\Registry;

class FixtureLoader
{
    /**
     * @var AliceLoader
     */
    protected $aliceLoader;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var AliceDoctrine
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
     * @param OroAliceLoader $aliceLoader
     */
    public function __construct(
        Registry $registry,
        EntityClassResolver $entityClassResolver,
        EntitySupplement $entitySupplement,
        OroAliceLoader $aliceLoader
    ) {
        $this->em = $registry->getManager();
        $this->persister = new AliceDoctrine($this->em);
        $this->fallbackPath = str_replace('/', DIRECTORY_SEPARATOR, __DIR__.'/../../Tests/Behat');
        $this->aliceLoader = $aliceLoader;
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

        $objects = $this->load($file);
        $this->persist($objects);
    }

    /**
     * @param string $entityName
     * @param TableNode $table
     */
    public function loadTable($entityName, TableNode $table)
    {
        $className = $this->getEntityClass($entityName);

        $rows = $table->getRows();
        $headers = array_shift($rows);

        foreach ($rows as $row) {
            $values = array_combine($headers, $row);
            $object = $this->getObjectFromArray($className, $values);

            $this->em->persist($object);
        }

        $this->em->flush();
    }

    /**
     * @param string $entity Entity name of full namespace
     * @param array $aliceValues Values in alice format, references, faker function can be used
     * @param array $objectValues Object values in format ['property' => 'value']
     * @return object Entity object instantiated and filled with values. All required values will filled by faker
     */
    public function getObjectFromArray($entity, array $aliceValues, array $objectValues = [])
    {
        $className = class_exists($entity) ? $entity : $this->getEntityClass($entity);

        $aliceFixture = $this->buildAliceFixture($className, $aliceValues);

        $objects = $this->load($aliceFixture);
        $object = array_shift($objects);
        $this->entitySupplement->completeRequired($object, $objectValues);

        return $object;
    }

    /**
     * @param string $entityName
     * @param integer $numberOfEntities
     * @return array Generated objects in format ['aliceReference' => object]
     */
    public function loadRandomEntities($entityName, $numberOfEntities)
    {
        $className = $this->getEntityClass($entityName);
        $entities = [];

        for ($i = 0; $i < $numberOfEntities; $i++) {
            $id = uniqid('alice_', true);
            $entities[$id] = $entity = new $className;
            $this->aliceLoader->getReferenceRepository()->set($id, $entity);

            $this->entitySupplement->completeRequired($entity);

            $this->em->persist($entity);
        }

        $this->em->flush();

        return $entities;
    }

    /**
     * @param string|array $dataOrFilename
     * @return array
     */
    public function load($dataOrFilename)
    {
        return $this->aliceLoader->load($dataOrFilename);
    }

    /**
     * @param string $entityName Entity name in plural or single form, e.g. Tasks, Calendar Event etc.
     * @return string Full namespace to class
     */
    public function getEntityClass($entityName)
    {
        return $this->entityClassResolver->getEntityClass($entityName);
    }

    /**
     * @param array $objects
     */
    public function persist(array $objects)
    {
        $this->persister->persist($objects);
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
