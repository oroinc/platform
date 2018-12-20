<?php

namespace Oro\Component\Testing\Unit;

/**
 * This trait can be used in unit tests to simplify testing of TWIG extensions.
 */
trait TwigExtensionTestCaseTrait
{
    /**
     * Returns an object that helps to build the dependency injection container for tests.
     *
     * @return TestContainerBuilder
     */
    protected static function getContainerBuilder()
    {
        return TestContainerBuilder::create();
    }

    /**
     * Executes TWIG function by its name.
     *
     * @param \Twig_Extension $extension
     * @param string          $name
     * @param array           $params
     *
     * @return mixed
     */
    protected static function callTwigFunction(\Twig_Extension $extension, $name, array $params)
    {
        $callable = null;
        $functions = $extension->getFunctions();
        foreach ($functions as $key => $function) {
            if ($function instanceof \Twig_SimpleFunction) {
                if ($function->getName() === $name) {
                    $callable = $function->getCallable();
                    break;
                }
            } elseif ($function instanceof \Twig_Function_Method) {
                if ($key === $name) {
                    $callable = $function->getCallable();
                    break;
                }
            }
        }

        if (null === $callable) {
            \PHPUnit\Framework\TestCase::fail(sprintf('The "%s" function was not found.', $name));
        }

        return call_user_func_array($callable, $params);
    }

    /**
     * Executes TWIG filter by its name.
     *
     * @param \Twig_Extension $extension
     * @param string          $name
     * @param array           $params
     *
     * @return mixed
     */
    protected static function callTwigFilter(\Twig_Extension $extension, $name, array $params)
    {
        $callable = null;
        $filters = $extension->getFilters();
        foreach ($filters as $key => $filter) {
            if ($filter instanceof \Twig_SimpleFilter) {
                if ($filter->getName() === $name) {
                    $callable = $filter->getCallable();
                    break;
                }
            } elseif ($filter instanceof \Twig_Filter_Method) {
                if ($key === $name) {
                    $callable = $filter->getCallable();
                    break;
                }
            }
        }

        if (null === $callable) {
            \PHPUnit\Framework\TestCase::fail(sprintf('The "%s" filter was not found.', $name));
        }

        return call_user_func_array($callable, $params);
    }
}
