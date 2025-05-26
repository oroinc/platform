<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EmailTemplateCandidates;

use Oro\Bundle\EmailBundle\EmailTemplateCandidates\EmailTemplateCandidatesProvider;
use Oro\Bundle\EmailBundle\EmailTemplateCandidates\EmailTemplateCandidatesProviderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use PHPUnit\Framework\TestCase;

class EmailTemplateCandidatesProviderTest extends TestCase
{
    public function testShouldReturnOriginalNameWhenNoProviders(): void
    {
        $provider = new EmailTemplateCandidatesProvider([]);

        self::assertEquals(['sample_name'], $provider->getCandidatesNames(new EmailTemplateCriteria('sample_name')));
    }

    public function testShouldReturnMergedNamesWhenHasProviders(): void
    {
        $emailTemplateCriteria = new EmailTemplateCriteria('sample_name');
        $templateContext = ['sample_key' => 'sample_value'];

        $innerProvider1 = $this->createMock(EmailTemplateCandidatesProviderInterface::class);
        $innerProvider1->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn(['@foo/sample_value_3', '@foo/sample_value_2']);

        $innerProvider2 = $this->createMock(EmailTemplateCandidatesProviderInterface::class);
        $innerProvider2->expects(self::once())
            ->method('getCandidatesNames')
            ->with($emailTemplateCriteria, $templateContext)
            ->willReturn(['@bar/sample_value_1']);

        $provider = new EmailTemplateCandidatesProvider([$innerProvider1, $innerProvider2]);

        self::assertEquals(
            ['@foo/sample_value_3', '@foo/sample_value_2', '@bar/sample_value_1', $emailTemplateCriteria->getName()],
            $provider->getCandidatesNames($emailTemplateCriteria, $templateContext)
        );
    }
}
