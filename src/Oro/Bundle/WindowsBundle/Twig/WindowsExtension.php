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
    /** Protect extension from infinite loop */
    private bool $rendered = false;

    public function __construct(
        protected readonly ContainerInterface $container
    ) {
    }

    #[\Override]
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
     */
    public function render(Environment $environment): string
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
     */
    public function renderFragment(AbstractWindowsState $windowState): string
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            WindowsStateManagerRegistry::class,
            WindowsStateRequestManager::class,
            FragmentHandler::class
        ];
    }

    protected function getWindowsStateManagerRegistry(): WindowsStateManagerRegistry
    {
        return $this->container->get(WindowsStateManagerRegistry::class);
    }

    protected function getWindowsStateRequestManager(): WindowsStateRequestManager
    {
        return $this->container->get(WindowsStateRequestManager::class);
    }

    protected function getFragmentHandler(): FragmentHandler
    {
        return $this->container->get(FragmentHandler::class);
    }
}
