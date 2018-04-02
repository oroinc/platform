<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Behat\Gherkin\Node\TableNode;
use Behat\Testwork\Suite\Suite;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\Exception\FileNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\OroAliceLoader as AliceLoader;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FixtureLoader
{
    /**
     * @var AliceLoader
     */
    protected $aliceLoader;

    /**
     * @var KernelInterface
     */
    protected $kernel;

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
     * @param KernelInterface $kernel
     * @param EntityClassResolver $entityClassResolver
     * @param EntitySupplement $entitySupplement
     * @param OroAliceLoader $aliceLoader
     */
    public function __construct(
        KernelInterface $kernel,
        EntityClassResolver $entityClassResolver,
        EntitySupplement $entitySupplement,
        OroAliceLoader $aliceLoader
    ) {
        $this->kernel = $kernel;
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
     * @param string $file Full path to yml file with fixture
     */
    public function loadFile($file)
    {
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
        $em = $this->getEntityManager();

        $rows = $table->getRows();
        $headers = array_shift($rows);
        array_walk($headers, function (&$header) {
            $header = ucfirst(preg_replace('/\s*/', '', $header));
        });

        foreach ($rows as $row) {
            array_walk($row, function (&$value) {
                if (0 === strpos($value, '[')) {
                    $value = explode(', ', trim($value, '[]'));
                }
            });
            $values = array_combine($headers, $row);
            $object = $this->getObjectFromArray($className, $values);

            $em->persist($object);
        }

        $em->flush();
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
        $em = $this->getEntityManager();
        $entities = [];

        for ($i = 0; $i < $numberOfEntities; $i++) {
            $id = uniqid('alice_', true);
            $entities[$id] = $entity = new $className;
            $this->aliceLoader->getReferenceRepository()->set($id, $entity);

            $this->entitySupplement->completeRequired($entity);

            $em->persist($entity);
        }

        $em->flush();

        return $entities;
    }

    /**
     * @param string|array $dataOrFilename
     * @return array
     */
    public function load($dataOrFilename)
    {
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        $this->aliceLoader->setDoctrine($doctrine);
        $result = $this->aliceLoader->load($dataOrFilename);

        return $result;
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
        $em = $this->getEntityManager();

        foreach ($objects as $object) {
            $em->persist($object);
        }

        $em->flush();
    }

    /**
     * @param string $filename
     * @return string Real path to file with fuxtures
     * @throws \InvalidArgumentException
     */
    public function findFile($filename)
    {
        if (false === strpos($filename, ':')) {
            throw new FileNotFoundException(
                'Please define a bundle name for fixtures e.g. "BundleName:fixture.yml"'
            );
        }

        list($bundleName, $filename) = explode(':', $filename);
        $bundlePath = $this->kernel->getBundle('!'.$bundleName)->getPath();
        $suitePaths = [sprintf('%s%sTests%2$sBehat%2$sFeatures', $bundlePath, DIRECTORY_SEPARATOR)];

        if (!$file = $this->findFileInPath($filename, $suitePaths)) {
            throw new FileNotFoundException(sprintf(
                'Can\'t find "%s" in pahts "%s"',
                $filename,
                implode(',', $suitePaths)
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

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->kernel->getContainer()->get('doctrine')->getManager();
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
     * @param string $name
     * @param object $instance
     */
    public function addReference($name, $instance)
    {
        $this->aliceLoader->getReferenceRepository()->set($name, $instance);
    }

    /**
     * @param string $name
     * @param string|null $instance
     * @return mixed
     */
    public function getReference($name, $property = null)
    {
        return $this->aliceLoader->getReferenceRepository()->find($name, $property);
    }
}
