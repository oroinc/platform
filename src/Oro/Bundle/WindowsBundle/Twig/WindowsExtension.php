<?php

namespace Oro\Bundle\WindowsBundle\Twig;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManager;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateRequestManager;

class WindowsExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_windows';

    /**
     * Protect extension from infinite loop
     *
     * @var bool
     */
    protected $rendered = false;

    /** @var WindowsStateManager */
    protected $windowsStateManager;

    /** @var WindowsStateRequestManager */
    protected $windowsStateRequestManager;

    /**
     * @param WindowsStateManager $windowsStateManager
     * @param WindowsStateRequestManager $windowsStateRequestManager
     */
    public function __construct(
        WindowsStateManager $windowsStateManager,
        WindowsStateRequestManager $windowsStateRequestManager
    ) {
        $this->windowsStateManager = $windowsStateManager;
        $this->windowsStateRequestManager = $windowsStateRequestManager;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return [
            'oro_windows_restore' => new \Twig_Function_Method(
                $this,
                'render',
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
            'oro_window_render_fragment' => new \Twig_Function_Method(
                $this,
                'renderFragment',
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
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

        return $environment->render(
            'OroWindowsBundle::states.html.twig',
            ['windowStates' => $this->windowsStateManager->getWindowsStates()]
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
        $windowState->setRenderedSuccessfully(false);

        try {
            $uri = $this->windowsStateRequestManager->getUri($windowState->getData());

            /** @var HttpKernelExtension $httpKernelExtension */
            $httpKernelExtension = $environment->getExtension('http_kernel');
            $windowState->setRenderedSuccessfully(true);

            return $httpKernelExtension->renderFragment($uri);
        } catch (NotFoundHttpException $e) {
            $this->windowsStateManager->deleteWindowsState($windowState->getId());
        } catch (\InvalidArgumentException $e) {
            $this->windowsStateManager->deleteWindowsState($windowState->getId());
        }

        return '';
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }
}
