<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;

class EmailBodyHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailBodyHelper */
    private $bodyHelper;

    protected function setUp(): void
    {
        $this->bodyHelper = new EmailBodyHelper();
    }
    /**
     * @dataProvider bodyData
     */
    public function testGetClearBody(string $bodyText, string $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->bodyHelper->getTrimmedClearText($bodyText));
    }

    public function bodyData(): array
    {
        $htmlTest = <<<HTMLTEXT
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <title>some title</title>
 <body style="padding:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
 <style type="text/css">body {font-family: Arial;}</style>
 <!- some comment here -->
<p>The body text</p> <script>alert('test');</script>
</body>
</html>
HTMLTEXT;

        return [
            'plain text' => ['test text', 'test text'],
            'text with css' => [
                '<style type="text/css">body {font-family: Arial;}</style> some text',
                'some text'
            ],
            'text with javascript' => [
                '<script type="text/javascript"> document.write (\'some text\'); </script> another text',
                'another text'
            ],
            'text with body tag' => [$htmlTest, 'The body text'],
            'text with non printed symbols' => ["some\ntext with\tsymbols", 'some text with symbols'],
            'text with non printed unicode symbols' => [
                "text \u{200b}\u{200c}\u{200d}\u{200e}\u{200f}\u{feff}",
                'text'
            ]
        ];
    }
}
