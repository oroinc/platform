<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Mailer\Checker;

use Oro\Bundle\EmailBundle\Form\Model\SmtpSettings;
use Oro\Bundle\EmailBundle\Mailer\Checker\ConnectionCheckerInterface;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpConnectionChecker;
use Oro\Bundle\EmailBundle\Mailer\Checker\SmtpSettingsChecker;
use Oro\Bundle\EmailBundle\Mailer\Transport\DsnFromSmtpSettingsFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class SmtpSettingsCheckerTest extends \PHPUnit\Framework\TestCase
{
    private DsnFromSmtpSettingsFactory|\PHPUnit\Framework\MockObject\MockObject $dsnFromSmtpSettingsFactory;

    private ConnectionCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $connectionChecker;

    private SmtpSettingsChecker $smtpSettingsChecker;

    protected function setUp(): void
    {
        $this->dsnFromSmtpSettingsFactory = $this->createMock(DsnFromSmtpSettingsFactory::class);
        $this->connectionChecker = $this->createMock(SmtpConnectionChecker::class);
        $this->smtpSettingsChecker = new SmtpSettingsChecker(
            $this->dsnFromSmtpSettingsFactory,
            $this->connectionChecker
        );
    }

    public function testCheckConnectionWithNotEligibleSmtpSettings(): void
    {
        $this->connectionChecker->expects(self::never())
            ->method(self::anything());

        $result = $this->smtpSettingsChecker->checkConnection(new SmtpSettings(), $error);

        self::assertFalse($result);
        self::assertEquals('Not eligible SmtpSettings are given', $error);
    }

    public function testCheckConnectionWithNoError(): void
    {
        $smtpSettings = new SmtpSettings('example.org', 25, 'ssl');
        $dsn = Dsn::fromString('smtp://example.org:25');

        $this->dsnFromSmtpSettingsFactory->expects(self::once())
            ->method('create')
            ->with($smtpSettings)
            ->willReturn($dsn);

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->with($dsn)
            ->willReturn(true);

        $result = $this->smtpSettingsChecker->checkConnection($smtpSettings, $error);

        self::assertTrue($result);
        self::assertEmpty($error);
    }

    public function testCheckConnectionWithError(): void
    {
        $smtpSettings = new SmtpSettings('example.org', 25, 'ssl');
        $dsn = Dsn::fromString('smtp://example.org:25');

        $this->dsnFromSmtpSettingsFactory->expects(self::once())
            ->method('create')
            ->with($smtpSettings)
            ->willReturn($dsn);

        $this->connectionChecker->expects(self::once())
            ->method('checkConnection')
            ->with($dsn)
            ->willReturnCallback(static function (Dsn $dsn, string &$error = null) {
                $error = 'Test exception message';

                return false;
            });

        $result = $this->smtpSettingsChecker->checkConnection($smtpSettings, $error);

        self::assertFalse($result);
        self::assertEquals('Test exception message', $error);
    }
}
