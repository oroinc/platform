<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\Fixtures;

use Oro\Bundle\ApiBundle\ApiDoc\RequestAwareResourceDocParserInterface;
use Oro\Bundle\ApiBundle\ApiDoc\ResourceDocParserInterface;

interface RequestAwareResourceDocParserStub extends ResourceDocParserInterface, RequestAwareResourceDocParserInterface
{
}
