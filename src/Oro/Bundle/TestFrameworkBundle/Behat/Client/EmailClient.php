<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Behat\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Utils;
use Laminas\Mime\Decode;
use Laminas\Mime\Message;

/**
 * The MailCatcher client for getting and purging emails
 */
class EmailClient
{
    public function __construct(private Client $client, private $url = 'http://127.0.0.1:1080/')
    {
        $url = getenv('ORO_MAILER_WEB_URL');
        if ($url) {
            $this->url = $url;
        }
    }

    /**
     * @return array<int, array{from: string, to: string, subject: string, body: string, rawBody: string}>
     */
    public function getMessages(): array
    {
        $data = $this->getJson($this->url.'/messages');
        $messages = [];
        foreach ($data as $message) {
            $messageDetails = $this->getJson($this->url.'/messages/'.$message['id'].'.json');
            if (!isset($messageDetails['source'])) {
                $plainMessage = $this->client->get($this->url . '/messages/' . $message['id'] . '.source');
                $messageDetails['source'] = $plainMessage->getBody()->getContents();
            }
            $message = array_merge($message, $messageDetails);
            Decode::splitMessage($message['source'], $headers, $body);
            if (str_starts_with($headers->get('contenttype')->getType(), 'multipart/')) {
                $boundary = $headers->get('contenttype')->getParameter('boundary');
                $mimeMessage = Message::createFromMessage($body, $boundary);
            } else {
                $mimeMessage = Message::createFromMessage($message['source']);
            }
            $messages[] = [
                'from' => $message['sender'],
                'to' => implode(' ', $message['recipients']),
                'subject' => $message['subject'],
                'body' => $mimeMessage->getParts()[0]->getRawContent(),
            ];
        }

        return $messages;
    }

    /**
     * @param array $options
     * @return void
     * @throws GuzzleException
     */
    public function purge(array $options = []): void
    {
        $this->client->delete($this->url . '/messages', $options);
    }

    private function getJson(string $url): array
    {
        $response = $this->client->get($url);

        return Utils::jsonDecode((string)$response->getBody(), true);
    }
}
