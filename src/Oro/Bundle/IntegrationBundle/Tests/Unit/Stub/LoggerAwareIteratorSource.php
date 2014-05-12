<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Stub;

use Psr\Log\LoggerAwareInterface;

interface LoggerAwareIteratorSource extends LoggerAwareInterface, \Iterator
{
}
