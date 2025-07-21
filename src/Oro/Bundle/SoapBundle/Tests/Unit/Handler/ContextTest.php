<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Oro\Bundle\SoapBundle\Handler\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContextTest extends TestCase
{
    private Request $request;
    private Response $response;
    private string $action;
    private object $controllerObject;

    private array $values = ['testKey' => 'testValue'];

    private Context $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = Request::create('test_uri');
        $this->response = new Response();
        $this->action = 'test_action';
        $this->controllerObject = new \stdClass();

        $this->context = new Context(
            $this->controllerObject,
            $this->request,
            $this->response,
            $this->action,
            $this->values
        );
    }

    public function testGetRequest(): void
    {
        $this->assertSame($this->request, $this->context->getRequest());
    }

    public function testGetResponse(): void
    {
        $this->assertSame($this->response, $this->context->getResponse());
    }

    public function testGetAction(): void
    {
        $this->assertSame($this->action, $this->context->getAction());
        $this->assertTrue($this->context->isAction($this->action));
    }

    public function testValuesAccessor(): void
    {
        $this->assertTrue($this->context->has('testKey'));
        $this->assertEquals('testValue', $this->context->get('testKey'));

        $this->assertEquals('testDefaultValue', $this->context->get('notExistingOne', 'testDefaultValue'));

        $this->context->set('testSecondKey', 'testSecondValue');
        $this->assertEquals('testSecondValue', $this->context->get('testSecondKey'));
    }
}
