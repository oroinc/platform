<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\TranslationBundle\Helper\TranslationHelper;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Oro\Component\Testing\Unit\EntityTrait;

class TranslationHelperTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

    /** @var TranslationHelper */
    protected $helper;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder(ManagerRegistry::class)->getMock();

        $this->translationManager = $this->getMockBuilder(TranslationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = new TranslationHelper($this->registry, $this->translationManager);
    }

    public function testGetValue()
    {
        $this->setValue($this->helper, 'values', ['locale1-domain1' => ['key1' => 'val1', 'key2' => 'val2']]);
        $this->assertEquals('val1', $this->helper->getValue('key1', 'locale1', 'domain1'));
    }


    public function testGetValueEmptyValues()
    {
        $this->setValue($this->helper, 'values', []);
        $this->assertEquals('key1', $this->helper->getValue('key1', 'locale1', 'domain1'));
    }
}
