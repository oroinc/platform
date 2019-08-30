<?php

namespace Oro\Bundle\ApiBundle\EventListener;

use FOS\RestBundle\EventListener\BodyListener as BaseBodyListener;
use JsonStreamingParser\Exception\ParsingException;
use JsonStreamingParser\Listener\InMemoryListener;
use JsonStreamingParser\Parser;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Decorates {@see \FOS\RestBundle\EventListener\BodyListener} to correct an exception message
 * if a request body contains an invalid JSON document.
 */
class BodyListener extends BaseBodyListener
{
    public function onKernelRequest(GetResponseEvent $event)
    {
        try {
            parent::onKernelRequest($event);
        } catch (BadRequestHttpException $e) {
            if ($e->getMessage() === 'Invalid json message received') {
                $content = $event->getRequest()->getContent();
                if (is_string($content) && '' !== $content) {
                    $errorMessage = $this->getInvalidJsonExceptionMessage($content);
                    if ($errorMessage) {
                        throw new BadRequestHttpException($errorMessage);
                    }
                }
            }
        }
    }

    /**
     * @param string $content
     *
     * @return string|null
     */
    private function getInvalidJsonExceptionMessage(string $content): ?string
    {
        $errorMessage = null;
        $stream = fopen('php://memory', 'r+');
        try {
            fwrite($stream, $content);
            \rewind($stream);
            $parser = new Parser($stream, new InMemoryListener());
            $parser->parse();
        } catch (ParsingException $e) {
            $errorMessage = sprintf('Invalid json message received. %s', $e->getMessage());
        } catch (\Throwable $e) {
            // ignore not parsing exceptions
        } finally {
            \fclose($stream);
        }

        return $errorMessage;
    }
}
