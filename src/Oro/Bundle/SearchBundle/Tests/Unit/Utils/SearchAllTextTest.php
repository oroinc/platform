<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Utils;

use Oro\Bundle\SearchBundle\Utils\SearchAllText;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class SearchAllTextTest extends TestCase
{
    public function testGetOperatorChoices(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);

        $translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
                ['oro.filter.form.label_type_contains', [], null, null, 'contains'],
                ['oro.filter.form.label_type_not_contains', [], null, null, 'does not contain']
            ]);

        $searchAllTextUtil = new SearchAllText($translator);

        $expected = [
            'contains' => 1,
            'does not contain' => 2,
        ];

        $this->assertEquals($expected, $searchAllTextUtil->getOperatorChoices());
    }
}
