<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Fixtures\Exception;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Test fixture for TransportExceptionInterface.
 *
 * We use a concrete exception class instead of mocking because TransportExceptionInterface
 * extends Throwable, which has final methods like getMessage() that cannot be mocked.
 */
class TransportException extends \RuntimeException implements TransportExceptionInterface
{
}
