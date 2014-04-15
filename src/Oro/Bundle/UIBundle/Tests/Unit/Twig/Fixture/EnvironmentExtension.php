<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

class EnvironmentExtension extends \Twig_Extension
{
    public function getTokenParsers()
    {
        return array(
            new EnvironmentTokenParser(),
        );
    }

    public function getNodeVisitors()
    {
        return array(
            new EnvironmentNodeVisitor(),
        );
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('foo_filter', 'foo_filter'),
        );
    }

    public function getTests()
    {
        return array(
            new \Twig_SimpleTest('foo_test', 'foo_test'),
        );
    }

    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('foo_function', 'foo_function'),
        );
    }

    public function getOperators()
    {
        return array(
            array('foo_unary' => array()),
            array('foo_binary' => array()),
        );
    }

    public function getGlobals()
    {
        return array(
            'foo_global' => 'foo_global',
        );
    }

    public function getName()
    {
        return 'environment_test';
    }
}
