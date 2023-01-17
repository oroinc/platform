<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Behat\Gherkin\Node\TableNode;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\MigrationBundle\Doctrine\ORM\Decorator\DataFixtureEntityManager;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\Exception\FileNotFoundException;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureLoader as AliceLoader;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Loads fixtures.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FixtureLoader
{
    private array $fileLoaderProcessorsStates = [];

    public function __construct(
        private KernelInterface $kernel,
        private EntityClassResolver $entityClassResolver,
        private EntitySupplement $entitySupplement,
        private AliceLoader $aliceLoader,
    ) {
    }

    public function loadFixtureFile(string $filename): void
    {
        $parameters = $this->processFileParametersBefore($filename);
        $file = $this->findFile($filename);
        $objects = $this->load($file);
        $this->persist($objects);

        $this->processFileParametersAfter($parameters);
    }

    /**
     * @param string $file Full path to yml file with fixture
     */
    public function loadFile(string $file)
    {
        $objects = $this->load($file);
        $this->persist($objects);
    }

    public function loadTable($entityName, TableNode $table): void
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
    public function getObjectFromArray(string $entity, array $aliceValues, array $objectValues = []): object
    {
        $className = class_exists($entity) ? $entity : $this->getEntityClass($entity);

        $aliceFixture = $this->buildAliceFixture($className, $aliceValues);

        $objects = $this->load($aliceFixture);
        $object = array_shift($objects);
        $this->entitySupplement->completeRequired($object, $objectValues);

        return $object;
    }

    /**
     * @param string|array $dataOrFilename
     * @return array
     */
    public function load($dataOrFilename): array
    {
        if (\is_string($dataOrFilename)) {
            $dataOrFilename = [$dataOrFilename];
        }
        $result = $this->aliceLoader->load($dataOrFilename);

        $helper = $this->kernel->getContainer()->get('oro_entity.doctrine_helper');
        foreach ($result as $key => $object) {
            if (!$helper->isManageableEntity($object)) {
                unset($result[$key]);
            }
        }

        return $result;
    }

    /**
     * @param string $entityName Entity name in plural or single form, e.g. Tasks, Calendar Event etc.
     * @return string Full namespace to class
     */
    public function getEntityClass(string $entityName): string
    {
        return $this->entityClassResolver->getEntityClass($entityName);
    }

    public function persist(array $objects)
    {
        $em = $this->getEntityManager();

        foreach ($objects as $object) {
            if (!$em->contains($object)) {
                $metadata = $em->getClassMetadata(\get_class($object));

                if (count($metadata->getIdentifier()) === 1
                    && $metadata->getSingleIdReflectionProperty()->getValue($object)
                ) {
                    $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_NONE);
                    $metadata->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
                }
            }
            $em->persist($object);
        }

        $em->flush();
    }

    /**
     * @param string $filename
     * @return string Real path to file with fixtures
     * @throws \InvalidArgumentException
     */
    public function findFile(string $filename): string
    {
        if (false === strpos($filename, ':')) {
            throw new FileNotFoundException(
                'Please define a bundle name for fixtures e.g. "BundleName:fixture.yml"'
            );
        }

        list($bundleName, $filename) = explode(':', $filename);
        $bundlePath = $bundleName !== 'app'
            ? $this->kernel->getBundle($bundleName)->getPath()
            : $this->kernel->getProjectDir().DIRECTORY_SEPARATOR.'src';
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
    private function findFileInPath(string $filename, array $paths): ?string
    {
        foreach ($paths as $path) {
            $file = $path.DIRECTORY_SEPARATOR.'Fixtures'.DIRECTORY_SEPARATOR.$filename;
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        $em = $this->kernel->getContainer()->get('oro_migration.data_fixture.entity_manager');
        if ($em instanceof DataFixtureEntityManager) {
            $em->setValidateBeforeFlush(false);
        }

        return $em;
    }

    /**
     * @param string $className FQCN
     * @param array $values
     * @return array
     */
    protected function buildAliceFixture(string $className, array $values): array
    {
        $entityReference = uniqid('', true);

        return [
            $className => [
                $entityReference => $values
            ]
        ];
    }

    public function addReference(string $name, object $instance): void
    {
        $this->aliceLoader->getReferenceRepository()->set($name, $instance);
    }

    /**
     * @param string $name
     * @param string|null $property
     * @return mixed
     */
    public function getReference(string $name, string $property = null)
    {
        return $this->aliceLoader->getReferenceRepository()->find($name, $property);
    }

    private function processFileParametersBefore(string &$fileName): array
    {
        $parameters = explode('?', $fileName);
        $fileName = $parameters[0];

        if (!isset($parameters[1])) {
            return [];
        }

        $parametersString = $parameters[1];
        $parameters = [];
        parse_str($parametersString, $parameters);

        foreach ($parameters as $parameterName => $parameterValue) {
            $this->processFileParameterBefore($parameterName, $parameterValue);
        }

        return $parameters;
    }

    private function processFileParametersAfter(array $parameters): void
    {
        foreach ($parameters as $parameterName => $parameterValue) {
            $this->processFileParameterAfter($parameterName, $parameterValue);
        }
    }

    private function processFileParameterBefore(string $parameterName, string $parameterValue): void
    {
        if ($parameterName === 'user') {
            $this->setupSecurityTokenByUsername($parameterValue);
        } elseif ($parameterName === 'user_reference') {
            $this->setupSecurityTokenByUserFromReferenceRepository($parameterValue);
        }
    }

    private function processFileParameterAfter(string $parameterName, string $parameterValue)
    {
        // $parameterValue is left for possible future use(other cases)
        if (in_array($parameterName, ['user', 'user_reference'], true)) {
            $this->restoreReplacedSecurityToken();
        }
    }

    public function setupSecurityTokenByUsername(string $userName): void
    {
        $this->fileLoaderProcessorsStates['securityToken'] = null;

        $userName = trim($userName);
        $doctrine = $this->kernel->getContainer()->get('doctrine');
        $user = $doctrine->getManager()
            ->getRepository(User::class)
            ->findOneByUsername($userName);
        if (!$user) {
            throw new \UnexpectedValueException(sprintf("User with username '%s' doesn't exists", $userName));
        }

        $this->setupSecurityTokenByUser($user);
    }

    private function setupSecurityTokenByUserFromReferenceRepository(string $userReferenceName): void
    {
        $this->fileLoaderProcessorsStates['securityToken'] = null;

        $userReferenceName = trim($userReferenceName);
        $user = $this->getReference($userReferenceName);
        if (!$user instanceof User) {
            throw new \UnexpectedValueException(sprintf("Reference '%s' is not of 'User' class", $userReferenceName));
        }

        $this->setupSecurityTokenByUser($user);
    }

    private function setupSecurityTokenByUser(User $user): void
    {
        $token = new UsernamePasswordOrganizationToken(
            $user,
            $user->getUsernameLowercase(),
            'main',
            $user->getOrganization(),
            $user->getUserRoles()
        );

        $this->fileLoaderProcessorsStates['securityToken'] = $this->setSecurityToken($token);
    }

    private function restoreReplacedSecurityToken(): void
    {
        $token = $this->fileLoaderProcessorsStates['securityToken'] ?? null;
        $this->setSecurityToken($token);
    }

    private function setSecurityToken(TokenInterface $token = null): ?TokenInterface
    {
        $tokenStorage = $this->kernel->getContainer()->get('security.token_storage');

        $oldToken = $tokenStorage->getToken();
        $tokenStorage->setToken($token);

        return $oldToken;
    }
}
