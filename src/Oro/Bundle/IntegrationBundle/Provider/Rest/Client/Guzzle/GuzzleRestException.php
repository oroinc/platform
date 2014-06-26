<?php

namespace Oro\Bundle\IntegrationBundle\Provider\Rest\Client\Guzzle;

use Guzzle\Common\Exception\GuzzleException;
use Guzzle\Http\Message\RequestInterface as GuzzleRequestInterface;
use Guzzle\Http\Message\Response as GuzzleResponse;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException as BaseException;

class GuzzleRestException extends BaseException
{
    /**
     * @param string|null $message
     * @param GuzzleRequestInterface|null $request
     * @param GuzzleResponse|null $response
     * @param \Exception $previous
     * @return GuzzleRestException
     */
    public static function create(
        $message = null,
        GuzzleRequestInterface $request = null,
        GuzzleResponse $response = null,
        \Exception $previous = null
    ) {
        $messageParts = [];

        if ($message) {
            $messageParts[] = '[error] ' . $message;
        }

        $message = 'REST error:';
        if (!$previous instanceof GuzzleException) {
            $message .= ' ' . $previous->getMessage();
            if ($request) {
                $messageParts[] = '[url] ' . $request->getUrl();
                $messageParts[] = '[method] ' . $request->getMethod();
            }

            if ($response) {
                $messageParts[] = '[status code] ' . $response->getStatusCode();
                $messageParts[] = '[reason phrase] ' . $response->getReasonPhrase();
            }
        } else {
            $messageParts[] = $previous->getMessage();
        }

        $message .= PHP_EOL . implode(PHP_EOL, $messageParts);

        return new static($message, 0, $previous);
    }
}
