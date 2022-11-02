<?php

namespace Oro\Bundle\SecurityBundle\Request;

use Oro\Component\PhpUtils\ReflectionUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * The decorator for HTTP kernel that sets cookie path for session cookie if application was installed in subfolder.
 */
class SessionHttpKernelDecorator implements HttpKernelInterface, TerminableInterface
{
    private const SESSION_OPTIONS_PARAMETER_NAME = 'session.storage.options';
    private const COOKIE_PATH_OPTION = 'cookie_path';

    /** @var HttpKernelInterface */
    protected $kernel;

    /** @var ContainerInterface */
    protected $container;

    /** @var array|null */
    private $collectedSessionOptions;

    public function __construct(
        HttpKernelInterface $kernel,
        ContainerInterface $container
    ) {
        $this->kernel = $kernel;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
    {
        if (null === $this->collectedSessionOptions) {
            $this->collectedSessionOptions = $this->applyBasePathToCookiePath(
                $request->getBasePath(),
                $this->getSessionOptions()
            );
        }
        $this->setSessionOptions($this->collectedSessionOptions);

        return $this->kernel->handle($request, $type, $catch);
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        if ($this->kernel instanceof TerminableInterface) {
            $this->kernel->terminate($request, $response);
        }
    }

    protected function applyBasePathToCookiePath(string $basePath, array $options): array
    {
        if ($basePath && '/' !== $basePath) {
            $existingCookiePath = $options[self::COOKIE_PATH_OPTION] ?? '/';
            $options[self::COOKIE_PATH_OPTION] = $basePath . $existingCookiePath;
        }

        return $options;
    }

    protected function getSessionOptions(): array
    {
        return $this->container->getParameter(self::SESSION_OPTIONS_PARAMETER_NAME);
    }

    protected function setSessionOptions(array $options): void
    {
        $parametersProperty = ReflectionUtil::getProperty(new \ReflectionClass($this->container), 'parameters');
        if (null === $parametersProperty) {
            throw new \LogicException(sprintf(
                'The class "%s" does not have "parameters" property.',
                get_class($this->container)
            ));
        }
        $parametersProperty->setAccessible(true);
        $parameters = $parametersProperty->getValue($this->container);
        $parameters[self::SESSION_OPTIONS_PARAMETER_NAME] = $options;
        $parametersProperty->setValue($this->container, $parameters);
    }
}
