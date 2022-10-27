<?php

namespace Oro\Bundle\WindowsBundle\Twig;

use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to display restored state of dialog window(s):
 *   - oro_windows_restore
 *   - oro_window_render_fragment
 */
class WindowsExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    protected ContainerInterface $container;
    /** Protect extension from infinite loop */
    private bool $rendered = false;

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
     * @return FragmentHandler
     */
    protected function getFragmentHandler()
    {
        return $this->container->get('fragment.handler');
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'oro_windows_restore',
                [$this, 'render'],
                ['needs_environment' => true, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'oro_window_render_fragment',
                [$this, 'renderFragment'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Renders windows restore html block
     *
     * @param Environment $environment
     *
     * @return string
     */
    public function render(Environment $environment)
    {
        if ($this->rendered) {
            return '';
        }

        $this->rendered = true;

        $manager = $this->getWindowsStateManagerRegistry()->getManager();
        $windowsStates = null !== $manager
            ? $manager->getWindowsStates()
            : [];

        return $environment->render(
            '@OroWindows/states.html.twig',
            ['windowStates' => $windowsStates]
        );
    }

    /**
     * Renders fragment by window state.
     *
     * @param AbstractWindowsState $windowState
     *
     * @return string
     */
    public function renderFragment(AbstractWindowsState $windowState)
    {
        $windowState->setRenderedSuccessfully(false);
        try {
            $uri = $this->getWindowsStateRequestManager()->getUri($windowState->getData());

            $result = $this->getFragmentHandler()->render($uri);
            $windowState->setRenderedSuccessfully(true);

            return $result;
        } catch (NotFoundHttpException $e) {
        } catch (\InvalidArgumentException $e) {
        }

        $manager = $this->getWindowsStateManagerRegistry()->getManager();
        if (null !== $manager) {
            $manager->deleteWindowsState($windowState->getId());
        }

        return '';
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_windows.manager.windows_state_registry' => WindowsStateManagerRegistry::class,
            'oro_windows.manager.windows_state_request' => WindowsStateRequestManager::class,
            'fragment.handler' => FragmentHandler::class,
        ];
    }
}
