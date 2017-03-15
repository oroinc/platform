<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Datagrid\Extension\MassAction\Handlers;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;
use Oro\Bundle\TranslationBundle\Datagrid\Extension\MassAction\ResetTranslationsMassActionHandler;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\Translation\TranslatorInterface;

class ResetTranslationsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ResetTranslationsMassActionHandler */
    protected $handler;

    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var TranslationManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $translationManager;

    /** @var MassActionHandlerArgs|\PHPUnit_Framework_MockObject_MockObject */
    protected $args;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translationManager = $this->createMock(TranslationManager::class);
        $this->args = $this->createMock(MassActionHandlerArgs::class);

        $this->handler = new ResetTranslationsMassActionHandler($this->translationManager, $this->translator);
    }

    public function testHandleWithoutValues()
    {
        $this->args->expects($this->once())->method('getData')->willReturn(['inset' => 0]);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.translation.action.reset.failure')
            ->willReturn('fail message');

        $response = $this->handler->handle($this->args);
        $this->assertEquals(new MassActionResponse(false, 'fail message'), $response);
    }

    public function testHandleWithoutInset()
    {
        $this->args->expects($this->once())->method('getData')->willReturn(['values' => '1,2,3']);
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.translation.action.reset.failure')
            ->willReturn('fail message');

        $response = $this->handler->handle($this->args);
        $this->assertEquals(new MassActionResponse(false, 'fail message'), $response);
    }

    public function testHandleWithResetAll()
    {
        $this->args->expects($this->once())->method('getData')->willReturn(['values' => '', 'inset' => '0']);
        $this->prepareTranslator();

        $count = 123;
        $this->translationManager->expects($this->once())->method('resetAllTranslations')->willReturn($count);

        $response = $this->handler->handle($this->args);
        $this->assertEquals(new MassActionResponse(true, 'success message', ['count' => $count]), $response);
    }

    /**
     * @dataProvider getTestData
     * @param string $idsValue
     * @param array $expected
     */
    public function testHandle($idsValue, array $expected)
    {
        $this->args->expects($this->once())->method('getData')->willReturn(['values' => $idsValue, 'inset' => '1']);
        $this->prepareTranslator();

        $count = 123;
        $this->translationManager->expects($this->once())
            ->method('resetTranslations')
            ->with($expected)
            ->willReturn($count);

        $response = $this->handler->handle($this->args);
        $this->assertEquals(new MassActionResponse(true, 'success message', ['count' => $count]), $response);
    }

    public function getTestData()
    {
        return [
            ['idsValues' => '1,2,3,4,5', 'expected' => [1, 2, 3, 4, 5]],
            ['idsValues' => '', 'expected' => []],
            ['idsValues' => ',,,,,', 'expected' => []],
            ['idsValues' => ',,,3,,4,,,5', 'expected' => [3, 4, 5]],
            ['idsValues' => '6,7,8,,,,', 'expected' => [6, 7, 8]],
        ];
    }

    protected function prepareTranslator()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.translation.action.reset.success')
            ->willReturn('success message');
    }
}
