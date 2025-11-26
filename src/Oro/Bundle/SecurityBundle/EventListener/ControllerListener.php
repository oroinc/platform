<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Oro\Bundle\SecurityBundle\Authorization\RequestAuthorizationChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks if access to an entity or a controller action is granted or not
 * Throws AccessDenied exception if access is not granted
 */
class ControllerListener
{
    public function __construct(
        private ClassAuthorizationChecker $classAuthorizationChecker,
        private LoggerInterface $logger,
        private ?RequestAuthorizationChecker $requestAuthorizationChecker = null,
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $this->checkEntityAccess($event);
        $this->checkControllerActionAccess($event);
    }

    /**
     * Checks if access to an entity is granted or not
     */
    private function checkEntityAccess(ControllerArgumentsEvent $event): void
    {
        $request = $event->getRequest();
        $request->attributes->set('_oro_access_checked', false);

        foreach ($event->getArguments() as $argument) {
            if (null !== $this->requestAuthorizationChecker && !$argument instanceof Request) {
                $granted = $this->requestAuthorizationChecker->isRequestObjectIsGranted($request, $argument);
                if ($granted === -1) {
                    $acl = $this->requestAuthorizationChecker->getRequestAcl($request);
                    throw new AccessDeniedException(
                        'You do not get ' . $acl?->getPermission() . ' permission for this object'
                    );
                }

                if ($granted === 1) {
                    $request->attributes->set('_oro_access_checked', true);
                }
            }
        }
    }

    /**
     * Checks if access to a controller action is granted or not
     */
    private function checkControllerActionAccess(ControllerArgumentsEvent $event): void
    {
        if (!$event->getRequest()->attributes->get('_oro_access_checked')) {
            $controller = $event->getController();
            /*
             * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
             * If it is a class, it comes in array format
             */
            if (is_array($controller)) {
                [$object, $method] = $controller;
                $className = ClassUtils::getRealClass($object);
                $this->checkController($className, $method, $event);
            } elseif (\is_object($controller) && \method_exists($controller, '__invoke')) {
                // Handle invokable controllers
                $className = ClassUtils::getRealClass($controller);
                $method = '__invoke';
                $this->checkController($className, $method, $event);
            }
        }
    }

    protected function checkController(string $className, mixed $method, ControllerArgumentsEvent $event): void
    {
        $this->logger->debug(
            sprintf(
                'Invoked controller "%s::%s". (%s)',
                $className,
                $method,
                $event->getRequestType() === HttpKernelInterface::MAIN_REQUEST
                    ? 'MAIN_REQUEST'
                    : 'SUB_REQUEST'
            )
        );

        if (!$this->classAuthorizationChecker->isClassMethodGranted($className, $method)) {
            if ($event->getRequestType() === HttpKernelInterface::MAIN_REQUEST) {
                throw new AccessDeniedException(sprintf('Access denied to %s::%s.', $className, $method));
            }
        }
    }
}
