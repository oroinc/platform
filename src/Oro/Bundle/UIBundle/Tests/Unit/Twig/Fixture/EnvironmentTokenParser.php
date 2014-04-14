<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

class EnvironmentTokenParser extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
    }

    public function getTag()
    {
        return 'test';
    }
}
