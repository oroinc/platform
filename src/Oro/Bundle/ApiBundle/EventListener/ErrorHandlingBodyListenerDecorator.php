<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\InMemoryListener;
use JsonStreamingParser\Parser;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Decorates {@see \FOS\RestBundle\EventListener\BodyListener} to correct an exception message
 * if a request body contains an invalid JSON document.
 */
class ErrorHandlingBodyListenerDecorator implements BodyListenerInterface
{
    private BodyListenerInterface $listener;

    public function __construct(BodyListenerInterface $listener)
    {
        $this->listener = $listener;
    }

    /**
     * {@inheritDoc}
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        try {
            $this->listener->onKernelRequest($event);
        } catch (BadRequestHttpException $e) {
            if ($e->getMessage() === 'Invalid json message received') {
                $content = $event->getRequest()->getContent();
                if (\is_string($content) && '' !== $content) {
                    $errorMessage = $this->getInvalidJsonExceptionMessage($content);
                    if ($errorMessage) {
                        throw new BadRequestHttpException($errorMessage);
                    }
                }
            }
            throw $e;
        }
    }

    private function getInvalidJsonExceptionMessage(string $content): ?string
    {
        $errorMessage = null;
        $stream = fopen('php://memory', 'rb+');
        try {
            fwrite($stream, $content);
            rewind($stream);
            $parser = new Parser($stream, new InMemoryListener());
            $parser->parse();
        } catch (ParsingException $e) {
            $errorMessage = sprintf(
                'Invalid json message received. %s',
                // remove invalid UTF-8 characters from the error message
                mb_convert_encoding($e->getMessage(), 'UTF-8', 'UTF-8')
            );
        } catch (\Throwable $e) {
            // ignore not parsing exceptions
        } finally {
            fclose($stream);
        }

        return $errorMessage;
    }
}
