<?php

namespace Oro\Bundle\UIBundle\Controller;

use FOS\RestBundle\Controller\ExceptionController as BaseController;
use FOS\RestBundle\Util\ExceptionValueMap;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\ExistsLoaderInterface;

/**
 * Handles rendering error pages.
 */
class ExceptionController extends BaseController implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var ViewHandlerInterface */
    private $viewHandler;

    /**
     * @param ContainerInterface $container
     * @param bool $showException
     */
    public function __construct(ContainerInterface $container, $showException)
    {
        $this->container = $container;
        $this->viewHandler = $container->get('fos_rest.view_handler');

        parent::__construct(
            $this->viewHandler,
            $this->container->get('fos_rest.exception.codes_map'),
            $showException
        );
    }

    protected function createView(\Exception $exception, $code, array $templateData, Request $request, $showException)
    {
        $view = new View(
            $exception,
            $code,
            $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : []
        );

        $format = $request->getRequestFormat();
        if ($this->viewHandler->isFormatTemplating($format)) {
            $view->setTemplate($this->findTemplate($request, $format, $code, $showException));
        }

        $view->setTemplateVar('raw_exception');
        $view->setTemplateData($templateData);

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    protected function findTemplate(Request $request, $format, $code, $showException)
    {
        $name = $showException ? 'exception' : 'error';
        if ($showException && 'html' == $format) {
            $name = 'exception_full';
        }

        // For error pages, try to find a template for the specific HTTP status code and format
        if (!$showException) {
            $template = sprintf('@Twig/Exception/%s%s.%s.twig', $name, $code, $format);

            if ($this->templateExists($template)) {
                return $template;
            }
        }

        // try to find a template for the given format
        $template = sprintf('@Twig/Exception/%s.%s.twig', $name, $format);

        if ($this->templateExists($template)) {
            return $template;
        }

        // default to a generic HTML exception
        $request->setRequestFormat('html');

        return sprintf('@Twig/Exception/%s.html.twig', $showException ? 'exception_full' : $name);
    }

    /**
     * @param string $template
     * @return bool
     */
    protected function templateExists($template)
    {
        $template = (string)$template;

        $loader = $this->container->get('twig')->getLoader();
        if ($loader instanceof ExistsLoaderInterface || method_exists($loader, 'exists')) {
            return $loader->exists($template);
        }

        try {
            $loader->getSourceContext($template)->getCode();

            return true;
        } catch (LoaderError $e) {
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return [
            'twig' => Environment::class,
            'fos_rest.view_handler' => ViewHandlerInterface::class,
            'fos_rest.exception.codes_map' => ExceptionValueMap::class,
        ];
    }
}
