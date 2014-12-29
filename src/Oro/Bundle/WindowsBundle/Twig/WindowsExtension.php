<?php

namespace Oro\Bundle\WindowsBundle\Twig;

use Doctrine\ORM\EntityManager;

use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class WindowsExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_windows';

    /**
     * @var SecurityContextInterface
     */
    protected $securityContext;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Protect extension from infinite loop
     *
     * @var bool
     */
    protected $rendered = false;

    /**
     * @param SecurityContextInterface $securityContext
     * @param EntityManager $em
     */
    public function __construct(
        SecurityContextInterface $securityContext,
        EntityManager $em
    ) {
        $this->securityContext = $securityContext;
        $this->em = $em;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'oro_windows_restore' => new \Twig_Function_Method(
                $this,
                'render',
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true
                )
            ),
            'oro_window_render_fragment' => new \Twig_Function_Method(
                $this,
                'renderFragment',
                array(
                    'is_safe' => array('html'),
                    'needs_environment' => true
                )
            )
        );
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
        if (!($user = $this->getUser()) || $this->rendered) {
            return '';
        }
        $this->rendered = true;

        $windowStates = array();
        $removeWindowStates = array();
        $userWindowStates = $this->em->getRepository('OroWindowsBundle:WindowsState')->findBy(array('user' => $user));
        /** @var WindowsState $windowState */
        foreach ($userWindowStates as $windowState) {
            $data = $windowState->getData();
            if (empty($data) || !isset($data['cleanUrl'])) {
                $this->em->remove($windowState);
                $removeWindowStates[] = $windowState;
            } else {
                $windowStates[] = $windowState;
            }
        }

        if ($removeWindowStates) {
            $this->em->flush($removeWindowStates);
        }

        return $environment->render(
            'OroWindowsBundle::states.html.twig',
            array('windowStates' => $windowStates)
        );
    }

    /**
     * Renders fragment by window state.
     *
     * @param \Twig_Environment $environment
     * @param WindowsState $windowState
     *
     * @return string
     */
    public function renderFragment(\Twig_Environment $environment, WindowsState $windowState)
    {
        $result = '';
        $windowState->setRenderedSuccessfully(false);
        $data = $windowState->getData();

        if (isset($data['cleanUrl'])) {
            if (isset($data['type'])) {
                $wid = isset($data['wid']) ? $data['wid'] : $this->getUniqueIdentifier();
                $uri = $this->getUrlWithContainer($data['cleanUrl'], $data['type'], $wid);
            } else {
                $uri = $data['cleanUrl'];
            }
        } else {
            return $result;
        }

        try {
            /** @var HttpKernelExtension $httpKernelExtension */
            $httpKernelExtension = $environment->getExtension('http_kernel');
            $result = $httpKernelExtension->renderFragment($uri);
            $windowState->setRenderedSuccessfully(true);
            return $result;
        } catch (NotFoundHttpException $e) {
            $this->em->remove($windowState);
            $this->em->flush($windowState);
        }

        return $result;
    }

    /**
     * Get a user from the Security Context
     *
     * @return null|mixed
     * @throws \LogicException If SecurityBundle is not available
     * @see Symfony\Component\Security\Core\Authentication\Token\TokenInterface::getUser()
     */
    public function getUser()
    {
        /** @var TokenInterface $token */
        if (null === $token = $this->securityContext->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }

    /**
     * @param string $url
     * @param string $container
     * @param string $wid
     *
     * @return string
     */
    protected function getUrlWithContainer($url, $container, $wid)
    {
        if (strpos($url, '_widgetContainer=') === false) {
            $parts = parse_url($url);
            $widgetPart = '_widgetContainer=' . $container. '&_wid=' . $wid;
            if (array_key_exists('query', $parts)) {
                $separator = $parts['query'] ? '&' : '';
                $newQuery = $parts['query'] . $separator . $widgetPart;
                $url = str_replace($parts['query'], $newQuery, $url);
            } else {
                $url .= '?' . $widgetPart;
            }
        }
        return $url;
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

    /**
     * @return string
     */
    protected function getUniqueIdentifier()
    {
        return str_replace('.', '-', uniqid('', true));
    }
}
