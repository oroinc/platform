<?php

namespace Oro\Bundle\SecurityBundle\EventListener;

use Oro\Bundle\SecurityBundle\Authorization\ClassAuthorizationChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ControllerListener
{
    /** @var ClassAuthorizationChecker */
    private $classAuthorizationChecker;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param ClassAuthorizationChecker $classAuthorizationChecker
     * @param LoggerInterface           $logger
     */
    public function __construct(
        ClassAuthorizationChecker $classAuthorizationChecker,
        LoggerInterface $logger
    ) {
        $this->classAuthorizationChecker = $classAuthorizationChecker;
        $this->logger = $logger;
    }

    /**
     * Checks if an access to a controller action is granted or not.
     *
     * This method is executed just before any controller action.
     *
     * @param FilterControllerEvent $event
     *
     * @throws AccessDeniedException
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        if (!$event->getRequest()->attributes->get('_oro_access_checked')) {
            $controller = $event->getController();
            /*
             * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
             * If it is a class, it comes in array format
             */
            if (is_array($controller)) {
                list($object, $method) = $controller;
                $className = ClassUtils::getRealClass($object);

                $this->logger->debug(
                    sprintf(
                        'Invoked controller "%s::%s". (%s)',
                        $className,
                        $method,
                        $event->getRequestType() === HttpKernelInterface::MASTER_REQUEST
                            ? 'MASTER_REQUEST'
                            : 'SUB_REQUEST'
                    )
                );

                if (!$this->classAuthorizationChecker->isClassMethodGranted($className, $method)) {
                    if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
                        throw new AccessDeniedException(sprintf('Access denied to %s::%s.', $className, $method));
                    }
                }
            }
        }
    }
}
