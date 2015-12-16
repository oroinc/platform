<?php

namespace Oro\Bundle\WindowsBundle\Twig;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\WindowsBundle\Entity\AbstractWindowsState;
use Oro\Bundle\WindowsBundle\Manager\WindowsStateManagerRegistry;
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

    /** @var WindowsStateManagerRegistry */
    protected $windowsStateManagerRegistry;

    /** @var WindowsStateRequestManager */
    protected $windowsStateRequestManager;

    /**
     * @param WindowsStateManagerRegistry $windowsStateManagerRegistry
     * @param WindowsStateRequestManager $windowsStateRequestManager
     */
    public function __construct(
        WindowsStateManagerRegistry $windowsStateManagerRegistry,
        WindowsStateRequestManager $windowsStateRequestManager
    ) {
        $this->windowsStateManagerRegistry = $windowsStateManagerRegistry;
        $this->windowsStateRequestManager = $windowsStateRequestManager;
    }

    /**
     * {@inheritdoc}
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

        try {
            $windowsStates = $this->windowsStateManagerRegistry->getManager()->getWindowsStates();
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
            $uri = $this->windowsStateRequestManager->getUri($windowState->getData());

            /** @var HttpKernelExtension $httpKernelExtension */
            $httpKernelExtension = $environment->getExtension('http_kernel');
            $result = $httpKernelExtension->renderFragment($uri);
            $windowState->setRenderedSuccessfully(true);

            return $result;
        } catch (NotFoundHttpException $e) {
            $scheduleDelete = true;
        } catch (\InvalidArgumentException $e) {
            $scheduleDelete = true;
        }

        if ($scheduleDelete) {
            try {
                $this->windowsStateManagerRegistry->getManager()->deleteWindowsState($windowState->getId());
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
