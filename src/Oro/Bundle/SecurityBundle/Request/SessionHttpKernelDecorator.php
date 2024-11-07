<?php

namespace Oro\Bundle\SecurityBundle\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * The decorator for HTTP kernel that sets cookie path for session cookie if application was installed in subfolder.
 */
class SessionHttpKernelDecorator implements HttpKernelInterface, TerminableInterface
{
    private ?array $originalSessionOptions = null;

    private ?array $currentSessionOptions = null;

    public function __construct(
        private HttpKernelInterface $innerKernel,
        private SessionStorageOptionsManipulator $sessionStorageOptionsManipulator
    ) {
    }

    #[\Override]
    public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
    {
        if (null === $this->originalSessionOptions) {
            $this->originalSessionOptions = $this->sessionStorageOptionsManipulator->getOriginalSessionOptions();
        }

        if ($this->currentSessionOptions === null) {
            $this->currentSessionOptions = $this->originalSessionOptions;
            $basePath = $request->getBasePath();

            if ($basePath && '/' !== $basePath) {
                $existingCookiePath = $this->originalSessionOptions['cookie_path'] ?? '/';
                $this->currentSessionOptions['cookie_path'] = $basePath . $existingCookiePath;
            }
        }

        $this->sessionStorageOptionsManipulator->setSessionOptions($this->currentSessionOptions);

        return $this->innerKernel->handle($request, $type, $catch);
    }

    #[\Override]
    public function terminate(Request $request, Response $response): void
    {
        if ($this->innerKernel instanceof TerminableInterface) {
            $this->innerKernel->terminate($request, $response);
        }
    }
}
