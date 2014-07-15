<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class OroTranslationPackCommandTest extends WebTestCase
{

    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * @dataProvider commandOptionsProvider
     */
    public function testCommand($commandName, array $params, $expectedContent = null, $notExpectedContent = null)
    {
        $result = $this->runCommand($commandName, $params);
        if (isset($expectedContent)) {
            $this->assertContains($expectedContent, $result);
        }
        if (isset($notExpectedContent)) {
            $this->assertNotContains($notExpectedContent, $result);
        }
    }

    public function commandOptionsProvider()
    {
        return [
            'upload files' => [
                'commandName' => 'oro:translation:pack',
                'params' => [
                    '--quiet' => true,
                    '--upload' => true,
                    'project' => 'OroCRM',
                    '--path' => '/../src/Oro/src/Oro/Bundle/TranslationBundle/Tests/Functional/Fixtures/Resources/' .
                    'language-pack'
                ],
                'expectedContent' => 'Some files require correction. Upload canceled.'
            ],
            'force upload files' => [
                'commandName' => 'oro:translation:pack',
                'params' => [
                    '--quiet' => true,
                    '--upload' => true,
                    '--skipCheck' => true,
                    'project' => 'OroCRM',
                    '--path' => '/../src/Oro/src/Oro/Bundle/TranslationBundle/Tests/Functional/Fixtures/Resources/' .
                    'language-pack'
                ],
                'expectedContent' => 'Force sending, without check files.'
            ],
            'upload check files complete' => [
                'commandName' => 'oro:translation:pack',
                'params' => [
                    '--quiet' => true,
                    '--upload' => true,
                    'project' => 'Oro',
                    '--path' => '/../src/Oro/src/Oro/Bundle/TranslationBundle/Tests/Functional/Fixtures/Resources/' .
                    'language-pack'
                ],
                'expectedContent' => null,
                'notExpectedContent' => 'Force sending, without check files.'
            ]
        ];
    }
}
