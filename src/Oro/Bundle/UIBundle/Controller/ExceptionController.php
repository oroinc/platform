<?php

namespace Oro\Bundle\UIBundle\Controller;

use FOS\RestBundle\Controller\ExceptionController as BaseController;
use Symfony\Component\HttpFoundation\Request;
use Twig\Error\LoaderError;
use Twig\Loader\ExistsLoaderInterface;

class ExceptionController extends BaseController
{
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
}
