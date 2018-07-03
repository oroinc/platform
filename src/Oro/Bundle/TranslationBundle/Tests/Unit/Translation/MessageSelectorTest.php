<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\MessageSelector;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MessageSelectorTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoggerInterface|MockObject */
    protected $logger;

    /** @var MessageSelector */
    protected $messageSelector;

    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->messageSelector = new MessageSelector($this->logger);
    }

    public function testReturnMessageIfExactlyOneStandardRuleIsGiven()
    {
        $this->assertEquals('There are two apples', $this->messageSelector->choose('There are two apples', 2, 'en'));
    }

    /**
     * @dataProvider getNonMatchingMessages
     * @param string $message
     * @param int $number
     * @param string $expected
     */
    public function testLogErrorIfMatchingMessageCannotBeFound($message, $number, $expected)
    {
        $this->logger->expects($this->once())->method('warning');

        $this->assertSame($expected, $this->messageSelector->choose($message, $number, 'ru'));
    }

    /**
     * @return array
     */
    public function getNonMatchingMessages()
    {
        return [
            ['{0} Ноль яблок|{1} Одно яблоко', 2, 'Одно яблоко'],
            ['{1} Одно яблоко|]1,Inf] тут %count% яблок', 0, 'тут %count% яблок'],
            ['{1} Одно яблоко|]2,Inf] тут %count% яблок', 2, 'тут %count% яблок'],
            ['{0} Ноль яблок|Одно яблоко|Два яблока', 100, 'Два яблока'],
            ['Одно яблоко|Два яблока', 100, 'Два яблока'],
            ['|', 15, '']
        ];
    }

    /**
     * @dataProvider getChooseTests
     * @param string $expected
     * @param string $message
     * @param int $number
     */
    public function testChoose($expected, $message, $number)
    {
        $this->logger->expects($this->never())->method('warning');

        $this->assertEquals($expected, $this->messageSelector->choose($message, $number, 'en'));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getChooseTests()
    {
        return [
            [
                'There are no apples',
                '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                0
            ],
            [
                'There are no apples',
                '{0}     There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                0
            ],
            [
                'There are no apples',
                '{0}There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                0
            ],

            [
                'There is one apple',
                '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                1
            ],

            [
                'There are %count% apples',
                '{0} There are no apples|{1} There is one apple|]1,Inf] There are %count% apples',
                10
            ],
            [
                'There are %count% apples',
                '{0} There are no apples|{1} There is one apple|]1,Inf]There are %count% apples',
                10
            ],
            [
                'There are %count% apples',
                '{0} There are no apples|{1} There is one apple|]1,Inf]     There are %count% apples',
                10
            ],

            ['There are %count% apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are %count% apples', 'There is one apple|There are %count% apples', 10],

            ['There are %count% apples', 'one: There is one apple|more: There are %count% apples', 0],
            ['There is one apple', 'one: There is one apple|more: There are %count% apples', 1],
            ['There are %count% apples', 'one: There is one apple|more: There are %count% apples', 10],

            [
                'There are no apples',
                '{0} There are no apples|one: There is one apple|more: There are %count% apples',
                0
            ],
            ['There is one apple', '{0} There are no apples|one: There is one apple|more: There are %count% apples', 1],
            [
                'There are %count% apples',
                '{0} There are no apples|one: There is one apple|more: There are %count% apples',
                10
            ],

            ['', '{0}|{1} There is one apple|]1,Inf] There are %count% apples', 0],
            ['', '{0} There are no apples|{1}|]1,Inf] There are %count% apples', 1],

            // Indexed only tests which are Gettext PoFile* compatible strings.
            ['There are %count% apples', 'There is one apple|There are %count% apples', 0],
            ['There is one apple', 'There is one apple|There are %count% apples', 1],
            ['There are %count% apples', 'There is one apple|There are %count% apples', 2],

            // Tests for float numbers
            [
                'There is almost one apple',
                '{0} There are no apples|]0,1[ There is almost one apple|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                0.7
            ],
            [
                'There is one apple',
                '{0} There are no apples|]0,1[There are %count% apples|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                1
            ],
            [
                'There is more than one apple',
                '{0} There are no apples|]0,1[There are %count% apples|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                1.7
            ],
            [
                'There are no apples',
                '{0} There are no apples|]0,1[There are %count% apples|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                0
            ],
            [
                'There are no apples',
                '{0} There are no apples|]0,1[There are %count% apples|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                0.0
            ],
            [
                'There are no apples',
                '{0.0} There are no apples|]0,1[There are %count% apples|{1}' .
                ' There is one apple|[1,Inf] There is more than one apple',
                0
            ],

            // Test texts with new-lines
            // with double-quotes and \n in id & double-quotes and actual newlines in text
            ["This is a text with a\n            new-line in it. Selector = 0.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 0],
            // with double-quotes and \n in id and single-quotes and actual newlines in text
            ["This is a text with a\n            new-line in it. Selector = 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            ["This is a text with a\n            new-line in it. Selector > 1.", '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            // with double-quotes and id split accros lines
            ['This is a text with a
            new-line in it. Selector = 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 1],
            // with single-quotes and id split accros lines
            ['This is a text with a
            new-line in it. Selector > 1.', '{0}This is a text with a
            new-line in it. Selector = 0.|{1}This is a text with a
            new-line in it. Selector = 1.|[1,Inf]This is a text with a
            new-line in it. Selector > 1.', 5],
            // with single-quotes and \n in text
            [
                'This is a text with a\nnew-line in it. Selector = 0.',
                '{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\nnew-line in it.' .
                ' Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.',
                0
            ],
            // with double-quotes and id split accros lines
            [
                "This is a text with a\nnew-line in it. Selector = 1.",
                "{0}This is a text with a\nnew-line in it. Selector = 0.|{1}This is a text with a\n" .
                "new-line in it. Selector = 1.|[1,Inf]This is a text with a\nnew-line in it. Selector > 1.",
                1
            ],
            // esacape pipe
            [
                'This is a text with | in it. Selector = 0.',
                '{0}This is a text with || in it. Selector = 0.|{1}This is a text with || in it. Selector = 1.',
                0
            ],
            // Empty plural set (2 plural forms) from a .PO file
            ['', '|', 1],
            // Empty plural set (3 plural forms) from a .PO file
            ['', '||', 1],
        ];
    }
}
