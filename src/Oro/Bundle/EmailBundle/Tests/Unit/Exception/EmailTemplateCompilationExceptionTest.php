<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Exception;

use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Twig\Error\Error;

class EmailTemplateCompilationExceptionTest extends \PHPUnit\Framework\TestCase
{
    private EmailTemplateCriteria $criteria;

    protected function setUp(): void
    {
        $this->criteria = $this->createMock(EmailTemplateCriteria::class);

        $this->criteria->method('getName')->willReturn('test.twig');
    }

    public function testThatExceptionHasCorrectMessageWhenNoneEntityName()
    {
        $exception = new EmailTemplateCompilationException($this->criteria);

        self::assertStringContainsString(
            'Could not compile one email template with "test.twig" name',
            $exception->getMessage()
        );
    }

    public function testThatExceptionHasCorrectMessageWhenHasEntityName()
    {
        $this->criteria->method('getEntityName')->willReturn('TestEntity');

        $exception = new EmailTemplateCompilationException($this->criteria);

        self::assertStringContainsString(
            'for "TestEntity" entity',
            $exception->getMessage()
        );
    }

    public function testThatExceptionHasCorrectMessageWhenPreviousExceptionIsSet()
    {
        $previousException = new Error('You cannot use __get in the template');

        $exception = new EmailTemplateCompilationException($this->criteria, $previousException);

        self::assertStringContainsString(
            'You cannot use __get in the template',
            $exception->getPrevious()->getMessage()
        );
    }
}
