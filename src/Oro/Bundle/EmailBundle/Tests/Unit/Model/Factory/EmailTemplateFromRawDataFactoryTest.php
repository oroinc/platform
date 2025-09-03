<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\Factory;

use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateFromArrayHydrator;
use Oro\Bundle\EmailBundle\EmailTemplateHydrator\EmailTemplateRawDataParser;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\Factory\EmailTemplateFromRawDataFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EmailTemplateFromRawDataFactoryTest extends TestCase
{
    private MockObject&EmailTemplateRawDataParser $templateRawDataParser;
    private MockObject&EmailTemplateFromArrayHydrator $emailTemplateFromArrayHydrator;

    protected function setUp(): void
    {
        $this->templateRawDataParser = $this->createMock(EmailTemplateRawDataParser::class);
        $this->emailTemplateFromArrayHydrator = $this->createMock(EmailTemplateFromArrayHydrator::class);
    }

    public function testCreateFromRawData(): void
    {
        $rawData = "@name = welcome\n@subject = Welcome!\n@type = html\n\nHello, {{ user.name }}!";
        $parsedData = [
            'name' => 'welcome',
            'subject' => 'Welcome!',
            'type' => 'html',
            'content' => 'Hello, {{ user.name }}!',
        ];

        $this->templateRawDataParser
            ->expects(self::once())
            ->method('parseRawData')
            ->with($rawData)
            ->willReturn($parsedData);

        $this->emailTemplateFromArrayHydrator
            ->expects(self::once())
            ->method('hydrateFromArray')
            ->with(
                self::callback(function ($template) {
                    // The factory must create an EmailTemplate instance
                    return $template instanceof EmailTemplate;
                }),
                $parsedData
            )
            ->willReturnCallback(function (EmailTemplate $template, array $data) {
                // Simulate hydration by setting properties
                $template->setName($data['name']);
                $template->setSubject($data['subject']);
                $template->setType($data['type']);
                $template->setContent($data['content']);
            });

        $factory = new EmailTemplateFromRawDataFactory(
            $this->templateRawDataParser,
            $this->emailTemplateFromArrayHydrator,
            EmailTemplate::class
        );

        $result = $factory->createFromRawData($rawData);

        self::assertSame('welcome', $result->getName());
        self::assertSame('Welcome!', $result->getSubject());
        self::assertSame('html', $result->getType());
        self::assertSame('Hello, {{ user.name }}!', $result->getContent());
    }
}
