<?php

namespace Oro\Bundle\TestFrameworkBundle\Test;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureFactory;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AliceFixtureIdentifierResolver;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\DataFixturesExecutor;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\DataFixturesLoader;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Resolver\ResolverInterface;
use Oro\Bundle\TestFrameworkBundle\Test\Event\DisableListenersForDataFixturesEvent;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\TestEventsLoggerTrait;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\PhpUtils\ArrayUtil;
use Oro\Component\Testing\DbIsolationExtension;
use PHPUnit\Framework\TestResult;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as BaseWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\StreamOutput;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\TokenStorage\SessionTokenStorage;
use Symfony\Component\Yaml\Yaml;

/**
 * Abstract class for functional and integration tests
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class WebTestCase extends BaseWebTestCase
{
    use DbIsolationExtension;
    use TestEventsLoggerTrait;

    /**
     * Use to isolate database changed between tests.
     * This adds a transaction that will be performed before a test starts and is rolled back when a test ends.
     */
    protected const DB_ISOLATION_PER_TEST_ANNOTATION = 'dbIsolationPerTest';

    /**
     * Use to avoid transaction rollbacks with Connection::transactional and missing on conflict in Doctrine
     * SQLSTATE[25P02] current transaction is aborted, commands ignored until end of transaction block
     */
    protected const NEST_TRANSACTIONS_WITH_SAVEPOINTS = 'nestTransactionsWithSavepoints';

    /** Default test API credentials */
    protected const USER_NAME = 'admin';

    /**  Default user name and password */
    protected const AUTH_USER = 'admin@example.com';
    protected const AUTH_PW = 'admin';
    protected const AUTH_ORGANIZATION = 1;

    /** BIG Integer number that does not exceed PostgreSQL INT */
    protected const BIGINT = 2147483647;

    /** @var string Default application kernel class */
    protected static $class = 'AppKernel';

    /** @var bool[] */
    private static $dbIsolationPerTest = [];

    /** @var bool[] */
    private static $nestTransactionsWithSavepoints = [];

    /** @var Client */
    private static $clientInstance;

    /** @var array */
    private static $loadedFixtures = [];

    /** @var Client */
    protected $client;

    /** @var ReferenceRepository[] */
    private array $referenceRepositories = [];

    /** @var ReferenceRepository */
    private static $referenceRepository;

    /** @var array */
    private static $afterInitClientMethods = [];

    /** @var array */
    private static $beforeResetClientMethods = [];

    /** @var bool */
    private static $initClientAllowed = false;

    #[\Override]
    protected function setUp(): void
    {
    }

    /**
     * @beforeClass
     */
    public static function setUpExtendEntityProcessor()
    {
        EntityExtendTestInitializer::initialize();
    }

    /**
     * In order to disable kernel shutdown
     * @see \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase::tearDown
     */
    #[\Override]
    protected function tearDown(): void
    {
    }

    /**
     * @after
     * @internal
     */
    protected function afterTest()
    {
        $this->client = null;

        if (self::isDbIsolationPerTest()) {
            if (self::$kernel) {
                self::$kernel->getContainer()->get('test.service_container')
                    ->get('oro_security.acl_query.cache_provider')
                    ->clear();
            }

            self::$loadedFixtures = [];
            self::$referenceRepository = null;

            self::rollbackTransaction();
            self::resetClient();
        }
    }

    /**
     * @internal
     * @afterClass
     */
    public static function afterClass()
    {
        self::$loadedFixtures = [];
        self::$referenceRepository = null;

        self::rollbackTransaction();
        self::resetClient();
    }

    #[\Override]
    public function run(?TestResult $result = null): TestResult
    {
        self::$initClientAllowed = true;

        return parent::run($result);
    }

    /**
     * Creates a Client.
     *
     * @param array $options An array of options to pass to the createKernel class
     * @param array $server An array of server parameters
     * @param bool $force If this option - true, will reset client on each initClient call
     *
     * @return Client A Client instance
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function initClient(array $options = [], array $server = [], $force = false)
    {
        if (!self::$initClientAllowed) {
            $callstack = '';
            foreach (debug_backtrace() as $frame) {
                if (!isset($frame['class'], $frame['function'], $frame['type'])) {
                    break;
                }
                $callstack .= '  ' . $frame['class'] . $frame['type'] . $frame['function'] . "()\n";
            }
            throw new \LogicException(
                'The initClient() must not be called in data providers.'
                . "\nCall stack:\n"
                . $callstack
            );
        }

        if (self::isClassHasAnnotation(get_called_class(), 'dbIsolation')) {
            throw new \RuntimeException(
                sprintf(
                    '@dbIsolation is default behavior now, please remove annotation from %s',
                    get_called_class()
                )
            );
        }

        if ($force) {
            throw new \RuntimeException(
                sprintf(
                    '%s::initClient asked to do force reset, use @%s instead',
                    get_called_class(),
                    self::DB_ISOLATION_PER_TEST_ANNOTATION
                )
            );
        }

        if (!self::$clientInstance) {
            if (self::$kernel) {
                throw new \RuntimeException(
                    sprintf(
                        '%s::initClient not allowed with booted kernel, call %s::resetClient first',
                        get_called_class(),
                        get_called_class()
                    )
                );
            }

            self::$clientInstance = self::createClient($options, $server);

            $this->checkRunEnvironment();
            $this->checkUserCredentials();
            $this->checkConfigurations();

            if (self::isClassHasAnnotation(get_called_class(), 'dbReindex')) {
                throw new \RuntimeException(
                    sprintf(
                        '%s::initClient asked to do reindex, use %s instead and add tearDown method',
                        get_called_class(),
                        SearchExtensionTrait::class
                    )
                );
            }

            $this->startTransaction(self::hasNestTransactionsWithSavepoints());
        } else {
            self::$clientInstance->setServerParameters($server);
            $this->checkRunEnvironment();
        }

        $hookMethods = self::getAfterInitClientMethods(\get_class($this));
        foreach ($hookMethods as $method) {
            $this->$method();
        }

        return $this->client = self::$clientInstance;
    }

    private static function getAfterInitClientMethods($className)
    {
        if (!isset(self::$afterInitClientMethods[$className])) {
            self::$afterInitClientMethods[$className] = [];

            try {
                $class = new \ReflectionClass($className);

                foreach ($class->getMethods() as $method) {
                    if (self::isClassHasAnnotation($className, 'afterInitClient', $method->getName())) {
                        \array_unshift(
                            self::$afterInitClientMethods[$className],
                            $method->getName()
                        );
                    }
                }
            } catch (\ReflectionException $e) {
            }
        }

        return self::$afterInitClientMethods[$className];
    }

    #[\Override]
    protected static function createKernel(array $options = []): KernelInterface
    {
        if (!array_key_exists('environment', $options)) {
            if (isset($_ENV['ORO_ENV'])) {
                $options['environment'] = $_ENV['ORO_ENV'];
            } elseif (isset($_SERVER['ORO_ENV'])) {
                $options['environment'] = $_SERVER['ORO_ENV'];
            }
        }

        if (!array_key_exists('debug', $options)) {
            if (isset($_ENV['ORO_DEBUG'])) {
                $options['debug'] = $_ENV['ORO_DEBUG'];
            } elseif (isset($_SERVER['ORO_DEBUG'])) {
                $options['debug'] = $_SERVER['ORO_DEBUG'];
            }
        }

        return parent::createKernel($options);
    }

    private static function getBeforeResetClientMethods($className)
    {
        if (!isset(self::$beforeResetClientMethods[$className])) {
            self::$beforeResetClientMethods[$className] = [];

            try {
                $class = new \ReflectionClass($className);

                /** @var \ReflectionMethod $method */
                foreach ($class->getMethods() as $method) {
                    if (self::isClassHasAnnotation($className, 'beforeResetClient', $method->getName())) {
                        if (!$method->isStatic()) {
                            throw new \RuntimeException(
                                sprintf('%s::%s should be static', $className, $method->getName())
                            );
                        }

                        \array_unshift(
                            self::$beforeResetClientMethods[$className],
                            $method->getName()
                        );
                    }
                }
            } catch (\ReflectionException $e) {
            }
        }

        return self::$beforeResetClientMethods[$className];
    }

    /**
     * @afterInitClient
     */
    protected function enableSearchListeners()
    {
        if ($this->getContainer()->hasParameter('optional_search_listeners')) {
            $optionalSearchListeners = $this->getContainer()->getParameter('optional_search_listeners');
            $this->getOptionalListenerManager()->enableListeners($optionalSearchListeners);
        }
    }

    /**
     * @afterInitClient
     */
    protected function disableOptionalListeners(): void
    {
        $manager = $this->getOptionalListenerManager();
        $manager->disableListeners($manager->getListeners());
    }

    protected function getOptionalListenerManager(): OptionalListenerManager
    {
        return self::getContainer()->get('oro_platform.optional_listeners.manager');
    }

    /**
     * @param string $tokenId
     * @return CsrfToken
     */
    protected function getCsrfToken(string $tokenId): CsrfToken
    {
        $this->ensureSessionIsAvailable();

        $container = self::getContainer();
        $session = $container->get('request_stack')->getSession();

        $tokenGenerator = $container->get('security.csrf.token_generator');
        $csrfToken = $tokenGenerator->generateToken();

        $session->set(SessionTokenStorage::SESSION_NAMESPACE . "/{$tokenId}", $csrfToken);
        $session->save();

        return new CsrfToken($tokenId, $csrfToken);
    }

    /**
     * @param string $login
     */
    protected function loginUser($login)
    {
        if ('' !== $login) {
            self::$clientInstance->setServerParameters(self::generateBasicAuthHeader($login, $login));
        } else {
            self::$clientInstance->setServerParameters([]);
            self::$clientInstance->getCookieJar()->clear();
        }
    }

    protected function updateUserSecurityToken(string $email): void
    {
        $user = $this->getUser($email);
        $token = new UsernamePasswordOrganizationToken(
            $user,
            'main',
            $user->getOrganization(),
            $user->getRoles()
        );
        self::getContainer()->get('security.token_storage')->setToken($token);
    }

    private function getUser(string $email, string $userClass = User::class): AbstractUser
    {
        return $this->getContainer()->get('doctrine')->getRepository($userClass)->findOneBy(['email' => $email]);
    }

    /**
     * Reset client
     */
    protected static function resetClient()
    {
        if (self::$clientInstance) {
            $hookMethods = self::getBeforeResetClientMethods(static::class);
            foreach ($hookMethods as $method) {
                static::$method();
            }
        }

        self::$clientInstance = null;

        self::ensureKernelShutdown();
        self::$kernel = null;
    }

    /**
     * Processes and replaces all references and functions to values.
     */
    protected static function processTemplateData(mixed $data): mixed
    {
        if (!self::$referenceRepository) {
            return $data;
        }

        if (is_string($data)) {
            try {
                $file = self::getContainer()->get('file_locator')->locate($data);
                if (is_file($file)) {
                    $data = Yaml::parse(file_get_contents($file));
                }
            } catch (\InvalidArgumentException $e) {
            }
        }

        $resolver = self::getReferenceResolver();

        if (is_array($data)) {
            array_walk_recursive($data, function (&$item) use ($resolver) {
                $item = $resolver->resolve($item);
            });
        } elseif (is_int($data) || is_string($data)) {
            $data = $resolver->resolve($data);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Expected argument of type "array or string", "%s" given.', gettype($data))
            );
        }

        return $data;
    }

    protected static function getReferenceResolver(): ResolverInterface
    {
        $resolver = self::getContainer()->get('oro_test.value_resolver');
        $resolver->setReferences(new Collection(self::$referenceRepository->getReferences()));

        return $resolver;
    }

    /**
     * Indicates whether each test is executed in own database transaction.
     * It is enabled by setting @dbIsolationPerTest annotation for the test class.
     *
     * @return bool
     */
    protected static function isDbIsolationPerTest()
    {
        $calledClass = get_called_class();
        if (!isset(self::$dbIsolationPerTest[$calledClass])) {
            self::$dbIsolationPerTest[$calledClass] = self::isClassHasAnnotation(
                $calledClass,
                self::DB_ISOLATION_PER_TEST_ANNOTATION
            );
        }

        return self::$dbIsolationPerTest[$calledClass];
    }

    /**
     * @return bool
     */
    private static function hasNestTransactionsWithSavepoints()
    {
        $calledClass = get_called_class();
        if (!isset(self::$nestTransactionsWithSavepoints[$calledClass])) {
            self::$nestTransactionsWithSavepoints[$calledClass] =
                self::isClassHasAnnotation($calledClass, self::NEST_TRANSACTIONS_WITH_SAVEPOINTS);
        }

        return self::$nestTransactionsWithSavepoints[$calledClass];
    }

    private static function isClassHasAnnotation(
        string $className,
        string $annotationName,
        ?string $methodName = null
    ): bool {
        $annotations = \PHPUnit\Util\Test::parseTestMethodAnnotations($className, $methodName);

        if ($methodName) {
            return !empty($annotations['method'][$annotationName]);
        }

        return !empty($annotations['class'][$annotationName]);
    }

    /**
     * Builds up the environment to run the given command.
     *
     * @param string $name
     * @param array $params
     * @param bool $cleanUp strip new lines and multiple spaces, removes dependency on terminal columns
     * @param bool $exceptionOnError
     *
     * @return string
     */
    protected static function runCommand($name, array $params = [], $cleanUp = true, $exceptionOnError = false)
    {
        $application = new Application(self::$kernel ?? self::bootKernel());
        $application->setAutoExit(false);

        putenv('COLUMNS=120');
        putenv('LINES=50');

        $params['--no-ansi'] = true;

        $args = ['application', $name];
        foreach ($params as $k => $v) {
            if (is_bool($v)) {
                if ($v) {
                    $args[] = $k;
                }
            } else {
                if (!is_int($k)) {
                    $args[] = $k;
                }
                $args[] = $v;
            }
        }
        $input = new ArgvInput($args);
        $input->setInteractive(false);

        $fp = fopen('php://temp/maxmemory:' . (1024 * 1024 * 1), 'br+');
        $output = new StreamOutput($fp);

        $exitCode = $application->run($input, $output);

        rewind($fp);

        $content = stream_get_contents($fp);

        if ($exceptionOnError && $exitCode !== 0) {
            throw new \RuntimeException($content);
        }

        if ($cleanUp) {
            $content = preg_replace(['/\s{2}\n\s{2}/', '/\n?(\s+)/'], ['', ' '], $content);
        }

        return trim($content);
    }

    /**
     * @param string[] $fixtures Each fixture can be a class name or a path to nelmio/alice file
     * @param bool     $force    Load fixtures even if its was loaded
     *
     * @link https://github.com/nelmio/alice
     */
    protected function loadFixtures(array $fixtures, $force = false)
    {
        if ($force) {
            throw new \RuntimeException(
                sprintf(
                    '%s::loadFixtures asked to do force load, use @%s instead',
                    get_called_class(),
                    self::DB_ISOLATION_PER_TEST_ANNOTATION
                )
            );
        }

        $container = $this->getContainer();
        $fixtureIdentifierResolver = new AliceFixtureIdentifierResolver($container->get('kernel'));

        $filteredFixtures = $this->filterFixtures($fixtures, $force);
        if (!$filteredFixtures) {
            return;
        }

        $loader = new DataFixturesLoader(new AliceFixtureFactory(), $fixtureIdentifierResolver, $container);
        foreach ($filteredFixtures as $fixture) {
            $loader->addFixture($fixture);
        }

        $executor = new DataFixturesExecutor(
            $this->getDataFixturesExecutorEntityManager(),
            $this->getContainer()->get('doctrine')
        );
        self::$referenceRepository = $executor->getReferenceRepository();
        $this->preFixtureLoad();
        $this->doLoadFixtures($executor, $loader);
        $this->postFixtureLoad();

        $this->referenceRepositories = $executor->getReferenceRepositories();
    }

    /**
     * @return string[]
     */
    protected function getListenersThatShouldBeDisabledDuringDataFixturesLoading()
    {
        $event = new DisableListenersForDataFixturesEvent();
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        $eventDispatcher->dispatch($event, DisableListenersForDataFixturesEvent::NAME);

        return $event->getListeners();
    }

    private function doLoadFixtures(DataFixturesExecutor $executor, DataFixturesLoader $loader)
    {
        $container = self::getContainer();
        /** @var TestBufferedMessageProducer|null $messageProducer */
        $messageProducer = $container->get(
            'oro_message_queue.client.buffered_message_producer',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        if (null !== $messageProducer && !$messageProducer instanceof TestBufferedMessageProducer) {
            $messageProducer = null;
        }

        // disable some listeners to speed up loading of fixtures
        $listenersToDisable = $this->getListenersThatShouldBeDisabledDuringDataFixturesLoading();
        foreach ($listenersToDisable as $listenerServiceId) {
            $container->get($listenerServiceId)->setEnabled(false);
        }
        // prevent sending of messages during loading of fixtures,
        // because fixtures are used to prepare data for tests
        // and it makes no sense to send messages before a test starts
        $restoreSendingOfMessages = false;
        if (null !== $messageProducer && !$messageProducer->isSendingOfMessagesStopped()) {
            $messageProducer->stopSendingOfMessages();
            $restoreSendingOfMessages = true;
        }
        try {
            $executor->execute($loader->getFixtures(), true);
        } finally {
            foreach ($listenersToDisable as $listenerServiceId) {
                $container->get($listenerServiceId)->setEnabled(true);
            }
            if ($restoreSendingOfMessages) {
                $messageProducer->restoreSendingOfMessages();
            }
        }
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * @param array  $fixtures Existing references that will be filtered
     * @param bool   $force    Load fixtures even if its was loaded
     * @return array
     */
    protected function filterFixtures(array $fixtures, $force = false)
    {
        $container = $this->getContainer();
        $fixtureIdentifierResolver = new AliceFixtureIdentifierResolver($container->get('kernel'));
        $filteredFixtures = [];

        foreach ($fixtures as $fixture) {
            $filteredFixtures[$fixtureIdentifierResolver->resolveId($fixture)] = $fixture;
        }

        if (!$force) {
            $removeLoadedFixturesCallback = function ($fixtureId) {
                return !in_array($fixtureId, self::$loadedFixtures, true);
            };

            $filteredFixtures = array_filter($filteredFixtures, $removeLoadedFixturesCallback, ARRAY_FILTER_USE_KEY);
        }

        self::$loadedFixtures = array_merge(self::$loadedFixtures, array_keys($filteredFixtures));

        return $filteredFixtures;
    }

    protected function isLoadedFixture(string $fixtureClass): bool
    {
        return in_array($fixtureClass, self::$loadedFixtures, true);
    }

    protected function getReference(string $name): object
    {
        return $this->getReferenceRepository()->getReference($name);
    }

    protected function hasReference(string $name): bool
    {
        return $this->getReferenceRepository()->hasReference($name);
    }

    protected function hasReferenceRepository(): bool
    {
        return null !== self::$referenceRepository;
    }

    protected function getReferenceRepository(?string $class = null): ReferenceRepository
    {
        if (null === self::$referenceRepository) {
            throw new \LogicException('The reference repository is not set. Have you loaded fixtures?');
        }

        if (null === $class) {
            return self::$referenceRepository;
        }

        $objectManager = $this->getContainer()->get('doctrine')->getManagerForClass($class);
        if (!$objectManager) {
            throw new \Exception(sprintf(
                'Reference repository is not created for class "%s". Did you forget'
                . ' to associate your object manager with class "%s"?',
                $class,
                $class
            ));
        }

        foreach ($this->referenceRepositories as $referenceRepository) {
            if ($objectManager === $referenceRepository->getManager()) {
                return $referenceRepository;
            }
        }

        throw new \Exception(sprintf(
            'Reference repository is not created for class "%s". '
                . 'Did you forget to extend Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture '
                . 'in your fixture to use not default ObjectManager?',
            $class
        ));
    }

    /**
     * Callback function to be executed before fixture load.
     */
    protected function preFixtureLoad()
    {
    }

    /**
     * Callback function to be executed after fixture load.
     */
    protected function postFixtureLoad()
    {
    }

    /**
     * Creates a mock object of a service identified by its id.
     *
     * @param string $id
     *
     * @return \PHPUnit\Framework\MockObject\MockBuilder
     */
    protected function getServiceMockBuilder($id)
    {
        $service = $this->getContainer()->get($id);
        $class = get_class($service);
        return $this->getMockBuilder($class)->disableOriginalConstructor();
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     *
     * @param string $name
     * @param array $parameters
     * @param bool|int $absolute
     *
     * @return string
     */
    protected function getUrl(string $name, array $parameters = [], bool|int $absolute = false)
    {
        $referenceType = $absolute;
        if (is_bool($absolute)) {
            $referenceType = $absolute ? UrlGeneratorInterface::ABSOLUTE_URL : UrlGeneratorInterface::ABSOLUTE_PATH;
        }

        return self::getContainer()->get('router')->generate($name, $parameters, $referenceType);
    }

    /**
     * @return Client
     * @throws \BadMethodCallException
     */
    protected static function getClientInstance()
    {
        if (!self::$clientInstance) {
            throw new \BadMethodCallException('Client instance is not initialized.');
        }

        return self::$clientInstance;
    }

    /**
     * Add value from 'oro_default' route to the url
     *
     * @param array $data
     * @param string $urlParameterKey
     */
    public function addOroDefaultPrefixToUrlInParameterArray(&$data, $urlParameterKey)
    {
        $oroDefaultPrefix = $this->getUrl('oro_default');

        $replaceOroDefaultPrefixCallback = function (&$value) use ($oroDefaultPrefix, $urlParameterKey) {
            if (is_array($value) && !is_null($value[$urlParameterKey])) {
                $value[$urlParameterKey] = str_replace(
                    '%oro_default_prefix%',
                    $oroDefaultPrefix,
                    $value[$urlParameterKey]
                );
            }
        };

        array_walk(
            $data,
            $replaceOroDefaultPrefixCallback
        );
    }

    /**
     * Calls a URI with emulation of AJAX request.
     *
     * @param string $method        The request method
     * @param string $uri           The URI to fetch
     * @param array  $parameters    The Request parameters
     * @param array  $files         The files
     * @param array  $server        The server parameters (HTTP headers are referenced with a HTTP_ prefix as PHP does)
     * @param string $content       The raw body data
     * @param bool   $changeHistory Whether to update the history or not
     *                              (only used internally for back(), forward(), and reload())
     *
     * @return Crawler
     */
    public function ajaxRequest(
        $method,
        $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        $content = null,
        $changeHistory = true
    ) {
        $csrfToken = 'nochecks';
        $cookieJar = $this->client->getCookieJar();
        $csrfTokenCookie = $cookieJar->get(CsrfRequestManager::CSRF_TOKEN_ID, '/', 'localhost');
        if ($csrfTokenCookie) {
            $csrfToken = $csrfTokenCookie->getValue();
        } else {
            $cookieJar->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken, null, '/', 'localhost'));
        }
        $server['HTTP_X-Requested-With'] = 'XMLHttpRequest';
        $server['HTTP_Content-type'] = 'application/json';
        $server['HTTP_' . CsrfRequestManager::CSRF_HEADER] = $csrfToken;

        return $this->client->request($method, $uri, $parameters, $files, $server, $content, $changeHistory);
    }

    /**
     * Data provider for REST/SOAP API tests
     *
     * @param string $folder
     *
     * @return array
     */
    public static function getApiRequestsData($folder)
    {
        static $randomString;

        // generate unique value
        if (!$randomString) {
            $randomString = self::generateRandomString(5);
        }

        $parameters = [];
        $testFiles = new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS);
        foreach ($testFiles as $fileName => $object) {
            $parameters[$fileName] = Yaml::parse(file_get_contents($fileName)) ?: [];
            if (is_null($parameters[$fileName]['response'])) {
                unset($parameters[$fileName]['response']);
            }
        }

        $replaceCallback = function (&$value) use ($randomString) {
            if (is_string($value)) {
                $value = str_replace('%str%', $randomString, $value);
            }
        };

        foreach ($parameters as $key => $value) {
            array_walk_recursive(
                $parameters[$key]['request'],
                $replaceCallback,
                $randomString
            );
            array_walk_recursive(
                $parameters[$key]['response'],
                $replaceCallback,
                $randomString
            );
        }

        return $parameters;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public static function generateRandomString($length = 10)
    {
        $random = "";
        mt_srand((float)microtime() * 1000000);
        $char_list = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $char_list .= "abcdefghijklmnopqrstuvwxyz";
        $char_list .= "1234567890_";

        for ($i = 0; $i < $length; $i++) {
            $random .= substr($char_list, (mt_rand() % (strlen($char_list))), 1);
        }

        return $random;
    }

    /**
     * Generate test API authorization header
     */
    public static function generateApiAuthHeader(
        string $userName = self::USER_NAME,
        ?int $organizationId = null
    ): array {
        return [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-API-TEST' => $organizationId ? $userName . '^' . $organizationId : $userName
        ];
    }

    /**
     * Generates Basic authorization header.
     */
    public static function generateBasicAuthHeader(
        string $userName = self::AUTH_USER,
        string $userPassword = self::AUTH_PW,
        int $userOrganization = self::AUTH_ORGANIZATION
    ): array {
        return [
            'PHP_AUTH_USER' => $userName,
            'PHP_AUTH_PW' => $userPassword,
            'HTTP_PHP_AUTH_ORGANIZATION' => $userOrganization,
        ];
    }

    public static function generateNoHashNavigationHeader(): array
    {
        return ['HTTP_' . strtoupper(ResponseHashnavListener::HASH_NAVIGATION_HEADER) => 0];
    }

    /**
     * Converts a JSON string to an array.
     */
    public static function jsonToArray(string $json): array
    {
        return $json
            ? (array)json_decode($json, true, 512, JSON_THROW_ON_ERROR)
            : [];
    }

    /**
     * Checks JSON response status code and returns the response content as an array.
     */
    public static function getJsonResponseContent(Response $response, int $statusCode, string $message = ''): array
    {
        self::assertJsonResponseStatusCodeEquals($response, $statusCode, $message);

        return self::jsonToArray($response->getContent());
    }

    /**
     * Assert response is json and has status code
     *
     * @param Response $response
     * @param int $statusCode
     * @param string $message
     */
    protected static function assertEmptyResponseStatusCodeEquals(Response $response, $statusCode, string $message = '')
    {
        self::assertResponseStatusCodeEquals($response, $statusCode, $message);
        self::assertEmpty(
            $response->getContent(),
            sprintf('HTTP response with code %d must have empty body', $statusCode)
        );
    }

    /**
     * Assert response is json and has status code
     *
     * @param Response $response
     * @param int $statusCode
     * @param string $message
     */
    protected static function assertJsonResponseStatusCodeEquals(Response $response, $statusCode, string $message = '')
    {
        self::assertResponseStatusCodeEquals($response, $statusCode, $message);
        self::assertResponseContentTypeEquals($response, 'application/json', $message);
    }

    /**
     * Assert response is html and has status code
     *
     * @param Response $response
     * @param int $statusCode
     * @param string $message
     */
    protected static function assertHtmlResponseStatusCodeEquals(Response $response, $statusCode, string $message = '')
    {
        self::assertResponseStatusCodeEquals($response, $statusCode, $message);
        self::assertResponseContentTypeEquals($response, 'text/html; charset=UTF-8', $message);
    }

    /**
     * Asserts response status code equals to the given status code.
     */
    protected static function assertResponseStatusCodeEquals(
        Response $response,
        int $statusCode,
        string $message = ''
    ): void {
        try {
            \PHPUnit\Framework\TestCase::assertEquals($statusCode, $response->getStatusCode(), $message);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            if ($statusCode < 400
                && $response->getStatusCode() >= 400
                && $response->headers->contains('Content-Type', 'application/json')
            ) {
                $content = self::jsonToArray($response->getContent());
                if (!empty($content['message'])) {
                    $errors = null;
                    if (!empty($content['errors'])) {
                        $errors = is_array($content['errors'])
                            ? json_encode($content['errors'])
                            : $content['errors'];
                    }
                    $e = new \PHPUnit\Framework\ExpectationFailedException(
                        $e->getMessage()
                        . ' Error message: ' . $content['message']
                        . ($errors ? '. Errors: ' . $errors : ''),
                        $e->getComparisonFailure()
                    );
                } else {
                    $e = new \PHPUnit\Framework\ExpectationFailedException(
                        $e->getMessage() . ' Response content: ' . $response->getContent(),
                        $e->getComparisonFailure()
                    );
                }
            }
            throw $e;
        }
    }

    /**
     * Asserts response has the given content type.
     */
    protected static function assertResponseContentTypeEquals(
        Response $response,
        string $contentType,
        string $message = ''
    ): void {
        $message = $message ? $message . PHP_EOL : '';
        $message .= sprintf('Failed asserting response has header "Content-Type: %s":', $contentType);
        $message .= PHP_EOL . $response->headers;
        $actualContentType = strtolower($response->headers->get('Content-Type'));
        $expectedContentType = strtolower($contentType);

        self::assertTrue(($actualContentType === $expectedContentType), $message);
    }

    /**
     * Asserts response contains the given content type.
     */
    protected static function assertResponseContentTypeContains(
        Response $response,
        string $contentType,
        string $message = ''
    ): void {
        $message = $message ? $message . PHP_EOL : '';
        $message .= sprintf('Failed asserting response "Content-Type" header contains "%s":', $contentType);
        $message .= PHP_EOL . $response->headers;
        self::assertTrue(
            $response->headers->has('Content-Type'),
            $message . PHP_EOL . 'The response does not have "Content-Type" header.'
        );
        self::assertStringContainsString($contentType, $response->headers->get('Content-Type'), $message);
    }

    /**
     * Asserts the given response header equals to the expected value.
     *
     * @param Response $response
     * @param string   $headerName
     * @param mixed    $expectedValue
     */
    protected static function assertResponseHeader(Response $response, string $headerName, string $expectedValue): void
    {
        self::assertEquals(
            $expectedValue,
            $response->headers->get($headerName),
            sprintf('"%s" response header', $headerName)
        );
    }

    /**
     * Asserts the given response header equals to the expected value.
     */
    protected static function assertResponseHeaderNotExists(Response $response, string $headerName): void
    {
        self::assertFalse(
            $response->headers->has($headerName),
            sprintf('"%s" header should not exist in the response', $headerName)
        );
    }

    /**
     * Asserts "Allow" response header equals to the expected value.
     */
    protected static function assertAllowResponseHeader(
        Response $response,
        string $expectedAllowedMethods,
        string $message = ''
    ): void {
        self::assertEquals($expectedAllowedMethods, $response->headers->get('Allow'), $message);
    }

    /**
     * Assert that intersect of $actual with $expected equals $expected
     */
    protected static function assertArrayIntersectEquals(array $expected, array $actual, string $message = '')
    {
        $actualIntersect = self::getRecursiveArrayIntersect($actual, $expected);
        \PHPUnit\Framework\TestCase::assertEquals(
            $expected,
            $actualIntersect,
            $message
        );
    }

    /**
     * Get intersect of $target array with values of keys in $source array.
     * If key is an array in both places then the value of this key will be returned as intersection as well.
     * Not associative arrays will be returned completely
     *
     * @param array $source
     * @param array $target
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected static function getRecursiveArrayIntersect(array $target, array $source)
    {
        $result = [];

        $isSourceAssociative = ArrayUtil::isAssoc($source);
        $isTargetAssociative = ArrayUtil::isAssoc($target);
        if (!$isSourceAssociative || !$isTargetAssociative) {
            foreach ($target as $key => $value) {
                if (array_key_exists($key, $source) && is_array($value) && is_array($source[$key])) {
                    $result[$key] = self::getRecursiveArrayIntersect($value, $source[$key]);
                } else {
                    $result[$key] = $value;
                }
            }
        } else {
            foreach (array_keys($source) as $key) {
                if (array_key_exists($key, $target)) {
                    if (is_array($target[$key]) && is_array($source[$key])) {
                        $result[$key] = self::getRecursiveArrayIntersect($target[$key], $source[$key]);
                    } else {
                        $result[$key] = $target[$key];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Sorts array by key recursively. This method is used to output failures of array response comparison in
     * a more comprehensive way.
     */
    protected static function sortArrayByKeyRecursively(array &$array)
    {
        ksort($array);

        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::sortArrayByKeyRecursively($value);
            }
        }
    }

    protected function getCurrentDir(): string
    {
        return dirname((new \ReflectionClass($this))->getFileName());
    }

    protected function getTestResourcePath(?string $folderName, string $fileName): string
    {
        if (!$folderName) {
            return $this->getCurrentDir() . DIRECTORY_SEPARATOR . $fileName;
        }

        return $this->getCurrentDir() . DIRECTORY_SEPARATOR .  $folderName . DIRECTORY_SEPARATOR . $fileName;
    }

    protected function isRelativePath(string $path): bool
    {
        return
            !str_starts_with($path, '/')
            && !str_starts_with($path, '@')
            && !str_contains($path, ':');
    }

    protected function createSession(): Session
    {
        if (!$this->client) {
            throw new \LogicException('Initialize the client before creating the session!');
        }

        $cookie = $this->client->getCookieJar()->get('MOCKSESSID');

        // create a new session object
        $container = self::getContainer();
        $session = $container->get('session.factory')->createSession();

        $domain = str_replace('http://', '', Client::LOCAL_URL);
        if ($cookie) {
            // get the session id from the session cookie if it exists
            $session->setId($cookie->getValue());
            $session->start();
        } else {
            // or create a new session id and a session cookie
            $session->start();
            $session->save();

            $sessionCookie = new Cookie(
                $session->getName(),
                $session->getId(),
                null,
                null,
                $domain,
            );
            $this->client->getCookieJar()->set($sessionCookie);
        }

        return $session;
    }

    protected function getSession(): ?SessionInterface
    {
        $this->ensureSessionIsAvailable();

        return self::getContainer()->get('request_stack')->getSession();
    }

    protected function ensureSessionIsAvailable(): void
    {
        $container = self::getContainer();
        $requestStack = $container->get('request_stack');

        try {
            $requestStack->getSession();
        } catch (SessionNotFoundException $e) {
            $session = $this->createSession();

            $mainRequest = Request::create('/');
            $mainRequest->setSession($session);

            $requestStack->push($mainRequest);

            $session->start();
            $session->save();

            $cookie = new Cookie($session->getName(), $session->getId());
            self::getClientInstance()->getCookieJar()->set($cookie);
        }
    }

    /**
     * Creates request needed for emulate request processing.
     *
     * @return void
     */
    protected function emulateRequest(): void
    {
        $request = new Request();
        $request->setSession($this->createSession());
        self::getContainer()->get('request_stack')->push($request);
    }

    private function checkRunEnvironment(): void
    {
        if (!self::$clientInstance->getContainer()->get(ApplicationState::class)->isInstalled()) {
            throw new \Exception(
                "You must install an application in the test environment ".
                "and try running the command in the test environment."
            );
        }
    }

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function checkUserCredentials(): void
    {
        $container = self::$clientInstance->getContainer();
        if ($this->getContainer()->hasParameter('optional_search_listeners')) {
            $optionalSearchListeners = $this->getContainer()->getParameter('optional_search_listeners');
            $this->getOptionalListenerManager()->enableListeners($optionalSearchListeners);
        }

        $defaultOptionsProvider = $container->get('oro_test.provider.install_default_options');

        $user = $container->get('oro_user.manager')->findUserByEmail(self::AUTH_USER);
        if (!$user) {
            $user = $container->get('oro_user.manager')->findUserByUsername(self::AUTH_PW);
        }

        //Check changes username
        $userName = $user->getUserIdentifier();
        if ($userName !== $defaultOptionsProvider->getUserName()) {
            throw new \Exception(
                sprintf(
                    'Username was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getUserName(),
                    $userName
                )
            );
        }

        //Check changes password
        $passwordHasher = $container->get('security.user_password_hasher');
        if (!$passwordHasher->isPasswordValid($user, self::AUTH_PW)) {
            throw new \Exception("User Password was changed after the application was installed");
        }

        //Check changes organization
        $organizationName = $user->getOrganization()?->getName();
        if ($organizationName !== $defaultOptionsProvider->getOrganizationName()) {
            throw new \Exception(
                sprintf(
                    'User Organization Name was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getOrganizationName(),
                    $organizationName
                )
            );
        }

        //Check changes user firsname
        $userFirsname = $user->getFirstName();
        if ($userFirsname !== $defaultOptionsProvider->getUserFirstName()) {
            throw new \Exception(
                sprintf(
                    'User Firstname was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getUserFirstName(),
                    $userFirsname
                )
            );
        }

        //Check changes user lastname
        $userLastname = $user->getLastName();
        if ($userLastname !== $defaultOptionsProvider->getUserLastName()) {
            throw new \Exception(
                sprintf(
                    'User Lastname was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getUserLastName(),
                    $userLastname
                )
            );
        }

        //Check changes user email
        $userEmail = $user->getEmail();
        if ($userEmail !== $defaultOptionsProvider->getUserEmail()) {
            throw new \Exception(
                sprintf(
                    'User EMAIL was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getUserEmail(),
                    $userEmail
                )
            );
        }
    }

    private function checkConfigurations(): void
    {
        $container = self::$clientInstance->getContainer();
        $defaultOptionsProvider = $container->get('oro_test.provider.install_default_options');

        //Check changes application url
        $url = $container->get('oro_config.manager')->get('oro_ui.application_url');
        if (rtrim($url, '/') !== rtrim($defaultOptionsProvider->getApplicationUrl(), '/')) {
            throw new \Exception(
                sprintf(
                    'Configuration "Application URL" was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getApplicationUrl(),
                    $url
                )
            );
        }

        $defaultLocalization = $container->get('oro_locale.manager.localization')->getDefaultLocalization();
        //Check changes language
        $language = $defaultLocalization?->getLanguageCode();
        if ($language !== $defaultOptionsProvider->getApplicationLanguage()) {
            throw new \Exception(
                sprintf(
                    'Configuration "Language" was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getApplicationLanguage(),
                    $language
                )
            );
        }

        //Check changes formatting code
        $formattingCode = $defaultLocalization?->getFormattingCode();
        if ($formattingCode !== $defaultOptionsProvider->getFormattingCode()) {
            throw new \Exception(
                sprintf(
                    'Configuration "Formatting code" was changed after the application was installed from "%s" to "%s"',
                    $defaultOptionsProvider->getFormattingCode(),
                    $formattingCode
                )
            );
        }
    }
}
