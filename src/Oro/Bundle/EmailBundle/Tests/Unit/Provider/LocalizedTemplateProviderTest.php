<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Model\DTO\LocalizedTemplateDTO;
use Oro\Bundle\EmailBundle\Model\EmailTemplate;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateContentProvider;
use Oro\Bundle\EmailBundle\Provider\LocalizedTemplateProvider;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Component\Testing\ReflectionUtil;

class LocalizedTemplateProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationProvider;

    /** @var EmailTemplateContentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $templateProvider;

    /** @var LocalizedTemplateProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->localizationProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->templateProvider = $this->createMock(EmailTemplateContentProvider::class);

        $this->provider = new LocalizedTemplateProvider($this->localizationProvider, $this->templateProvider);
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    public function testGetAggregatedInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Recipients should be array of EmailHolderInterface values, "stdClass" type in array given.'
        );

        $this->provider->getAggregated([new \stdClass()], new EmailTemplateCriteria('template_name'), []);
    }

    public function testGetAggregatedLogicExceptionException(): void
    {
        $recipient = new EmailAddressWithContext('to@mail.com');

        $this->localizationProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($recipient)
            ->willReturn(null);

        $this->expectException(\LogicException::class);

        $this->provider->getAggregated([$recipient], new EmailTemplateCriteria('template_name'), []);
    }

    public function testGetAggregated(): void
    {
        $recipientA = new EmailAddressWithContext('to1@mail.com');
        $recipientB = new EmailAddressWithContext('to2@mail.com');
        $recipientC = new EmailAddressWithContext('to3@mail.com');
        $recipientD = new EmailAddressWithContext('to4@mail.com');

        $criteria = new EmailTemplateCriteria('template_name');

        $localizationA = $this->getLocalization(42);
        $localizationB = $this->getLocalization(54);

        $templateA = (new EmailTemplate())->setSubject('Subject A');
        $templateB = (new EmailTemplate())->setSubject('Subject B');

        $this->localizationProvider->expects($this->exactly(4))
            ->method('getPreferredLocalization')
            ->withConsecutive(
                [$recipientA],
                [$recipientB],
                [$recipientC],
                [$recipientD]
            )
            ->willReturnOnConsecutiveCalls(
                $localizationA,
                $localizationB,
                $localizationA,
                $localizationB
            );

        $this->templateProvider->expects($this->exactly(2))
            ->method('getTemplateContent')
            ->withConsecutive(
                [$criteria, $localizationA, ['any-key' => 'any-val']],
                [$criteria, $localizationB, ['any-key' => 'any-val']]
            )
            ->willReturnOnConsecutiveCalls(
                $templateA,
                $templateB
            );

        $aggregation = $this->provider->getAggregated(
            [$recipientA, $recipientB, $recipientC, $recipientD],
            $criteria,
            ['any-key' => 'any-val']
        );

        $this->assertEquals([
            42 => (new LocalizedTemplateDTO($templateA))
                ->addRecipient($recipientA)
                ->addRecipient($recipientC),

            54 => (new LocalizedTemplateDTO($templateB))
                ->addRecipient($recipientB)
                ->addRecipient($recipientD),
        ], $aggregation);
    }
}
