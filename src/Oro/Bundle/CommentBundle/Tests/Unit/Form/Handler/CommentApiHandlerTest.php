<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CommentBundle\Entity\Comment;
use Oro\Bundle\CommentBundle\Form\Handler\CommentApiHandler;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CommentApiHandlerTest extends \PHPUnit\Framework\TestCase
{
    private FormInterface|\PHPUnit\Framework\MockObject\MockObject $form;

    private Request $request;

    private ObjectManager|\PHPUnit\Framework\MockObject\MockObject $om;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private Comment $comment;

    private CommentApiHandler $handler;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($this->request);
        $this->om = $this->createMock(ObjectManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->comment = new Comment();

        $this->handler = new CommentApiHandler($this->form, $requestStack, $this->om, $this->configManager);
    }

    /**
     * @dataProvider getTestData
     */
    public function testRequest(string $type, int $callsCount, bool $isValid, bool $isExpects): void
    {
        $persistCallsCount = 0;
        if ($isValid && $callsCount) {
            $persistCallsCount = 1;
        }
        $this->request->setMethod($type);

        $this->form->expects($this->exactly($callsCount))
            ->method('submit');

        $this->form->expects($this->exactly($callsCount))
            ->method('isValid')
            ->willReturn($isValid);

        $this->om->expects($this->exactly($persistCallsCount))
            ->method('persist')
            ->with($this->equalTo($this->comment));
        $this->om->expects($this->exactly($persistCallsCount))
            ->method('flush');

        $this->assertEquals($isExpects, $this->handler->process($this->comment));
    }

    public function getTestData(): array
    {
        return [
            'correct request type GET' => ['GET', 0, true, false],
            'incorrect request type GET' => ['GET', 0, false, false],
            'correct request type DELETE' => ['DELETE', 0, true, false],
            'incorrect request type DELETE' => ['DELETE', 0, false, false],
            'correct request type POST' => ['POST', 1, true, true],
            'incorrect request type POST' => ['POST', 1, false, false],
            'correct request type PUT' => ['PUT', 1, true, true],
            'incorrect request type PUT' => ['PUT', 1, false, false],
        ];
    }
}
