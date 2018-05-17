<?php

namespace Oro\Bundle\WindowsBundle\Twig;

use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class WindowsExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_windows';

    /** @var ContainerInterface */
    protected $container;

    /**
     * Protect extension from infinite loop
     *
     * @var bool
     */
    protected $rendered = false;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return WindowsStateManagerRegistry
     */
    protected function getWindowsStateManagerRegistry()
    {
        return $this->container->get('oro_windows.manager.windows_state_registry');
    }

    /**
     * @return WindowsStateRequestManager
     */
    protected function getWindowsStateRequestManager()
    {
        return $this->container->get('oro_windows.manager.windows_state_request');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_windows_restore',
                [$this, 'render'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'oro_window_render_fragment',
                [$this, 'renderFragment'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Renders windows restore html block
     *
     * @param \Twig_Environment $environment
     *
     * @return string
     */
    public function render(\Twig_Environment $environment)
    {
        if ($this->rendered) {
            return '';
        }

        $this->rendered = true;

        try {
            $windowsStates = $this->getWindowsStateManagerRegistry()->getManager()->getWindowsStates();
        } catch (AccessDeniedException $e) {
            $windowsStates = [];
        }

        return $environment->render(
            'OroWindowsBundle::states.html.twig',
            ['windowStates' => $windowsStates]
        );
    }

    /**
     * Renders fragment by window state.
     *
     * @param \Twig_Environment $environment
     * @param AbstractWindowsState $windowState
     *
     * @return string
     */
    public function renderFragment(\Twig_Environment $environment, AbstractWindowsState $windowState)
    {
        $result = '';
        $scheduleDelete = false;
        $windowState->setRenderedSuccessfully(false);

        try {
            $uri = $this->getWindowsStateRequestManager()->getUri($windowState->getData());

            /** @var FragmentHandler $fragmentHandler */
            $fragmentHandler = $this->container->get('fragment.handler');
            $result = $fragmentHandler->render($uri);
            $windowState->setRenderedSuccessfully(true);

            return $result;
        } catch (NotFoundHttpException $e) {
            $scheduleDelete = true;
        } catch (\InvalidArgumentException $e) {
            $scheduleDelete = true;
        }

        if ($scheduleDelete) {
            try {
                $this->getWindowsStateManagerRegistry()->getManager()->deleteWindowsState($windowState->getId());
            } catch (AccessDeniedException $e) {
                return $result;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
