<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;

class EmailBodyHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider bodyData
     */
    public function testGetClearBody($bodyText, $expectedResult)
    {
        $this->assertEquals($expectedResult, EmailBodyHelper::getClearBody($bodyText));
    }

    public function bodyData()
    {
        $htmlTest = <<<HTMLTEXT
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
 <title>some title</title>
 <body style="padding:0;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;">
The body text
</body>
</html>
HTMLTEXT;

        $expectedText = <<<EXPECTED

The body text

EXPECTED;

        return [
            'plain text' => ['test text', 'test text'],
            'text with css' => [
                '<style type="text/css">body {font-family: Arial;}</style> some text',
                ' some text'
            ],
            'text with javascript' => [
                '<script type="text/javascript"> document.write (\'some text\'); </script> another text',
                ' another text'
            ],
            'text with body tag' => [$htmlTest, $expectedText]
        ];
    }
}
