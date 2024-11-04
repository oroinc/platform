<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class EnvironmentTokenParser extends AbstractTokenParser
{
    #[\Override]
    public function parse(Token $token)
    {
    }

    #[\Override]
    public function getTag()
    {
        return 'test';
    }
}
