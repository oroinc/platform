<?php

namespace Oro\Component\Testing\Unit;

use Twig\Extension\AbstractExtension;
use Twig\Loader\LoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

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
     * @param AbstractExtension $extension
     * @param string $name
     * @param array $params
     *
     * @return mixed
     */
    protected static function callTwigFunction(AbstractExtension $extension, $name, array $params)
    {
        $callable = null;
        $functions = $extension->getFunctions();
        foreach ($functions as $key => $function) {
            if ($function instanceof TwigFunction && $function->getName() === $name) {
                $callable = $function->getCallable();
                break;
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
     * @param AbstractExtension $extension
     * @param string          $name
     * @param array           $params
     *
     * @return mixed
     */
    protected static function callTwigFilter(AbstractExtension $extension, $name, array $params)
    {
        $callable = null;
        $filters = $extension->getFilters();
        foreach ($filters as $key => $filter) {
            if ($filter instanceof TwigFilter && $filter->getName() === $name) {
                $callable = $filter->getCallable();
                break;
            }
        }

        if (null === $callable) {
            \PHPUnit\Framework\TestCase::fail(sprintf('The "%s" filter was not found.', $name));
        }

        return call_user_func_array($callable, $params);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoaderInterface
     */
    protected function getLoader()
    {
        return $this->createMock(LoaderInterface::class);
    }
}
