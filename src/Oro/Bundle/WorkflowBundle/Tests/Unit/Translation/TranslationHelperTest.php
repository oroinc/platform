<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\WorkflowBundle\Translation\TranslationHelper;

class TranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    const NODE = 'test_node';
    const ATTRIBUTE_NAME = 'test_attr_name';

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    private $manager;

    /** @var TranslationHelper */
    private $helper;

    protected function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)->disableOriginalConstructor()->getMock();
        $this->manager = $this->getMockBuilder(TranslationManager::class)->disableOriginalConstructor()->getMock();
        $this->helper = new TranslationHelper($this->translator, $this->manager);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->manager, $this->helper);
    }

    public function testSaveTranslation()
    {
        // current locale retrieve only once
        $this->translator->expects($this->once())->method('getLocale')->willReturn('en');
        $this->manager
            ->expects($this->exactly(2))
            ->method('saveValue')
            ->with('test_key', 'test_value', 'en', TranslationHelper::WORKFLOWS_DOMAIN);
        $this->helper->saveTranslation('test_key', 'test_value');
        $this->helper->saveTranslation('test_key', 'test_value');
    }

    public function testEnsureTranslationKey()
    {
        $key = 'key_to_be_sure_that_exists';
        $this->manager->expects($this->once())->method('findTranslationKey')->with($key, 'workflows');
        $this->helper->ensureTranslationKey($key);
    }

    public function testRemoveTranslationKey()
    {
        $key = 'key_to_remove';
        $this->manager->expects($this->once())->method('removeTranslationKey')->with($key, 'workflows');
        $this->helper->removeTranslationKey($key);
    }
}
