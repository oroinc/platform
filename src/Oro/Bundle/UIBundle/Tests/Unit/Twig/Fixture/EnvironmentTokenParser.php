<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class EnvironmentTokenParser extends AbstractTokenParser
{
    public function parse(Token $token)
    {
    }

    public function getTag()
    {
        return 'test';
    }
}
