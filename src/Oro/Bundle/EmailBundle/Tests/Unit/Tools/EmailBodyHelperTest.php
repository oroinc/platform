<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Tools;

use Oro\Bundle\EmailBundle\Tools\EmailBodyHelper;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;

class EmailBodyHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var EmailBodyHelper */
    protected $bodyHelper;

    protected function setUp()
    {
        $htmlTagProvider = $this->getMockBuilder('Oro\Bundle\FormBundle\Provider\HtmlTagProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->htmlTagHelper = new HtmlTagHelper($htmlTagProvider);
        $this->bodyHelper = new EmailBodyHelper($this->htmlTagHelper);
    }
    /**
     * @dataProvider bodyData
     */
    public function testGetClearBody($bodyText, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->bodyHelper->getClearBody($bodyText));
    }

    public function bodyData()
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
            'text with non printed symbols' => ["some\ntext with\tsymbols", 'some text with symbols']
        ];
    }
}
