<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Oro\Bundle\SoapBundle\Handler\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    /** @var string */
    private $action;

    /** @var object */
    private $controllerObject;

    /** @var array */
    private $values = ['testKey' => 'testValue'];

    /** @var Context */
    private $context;

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

    public function testGetRequest()
    {
        $this->assertSame($this->request, $this->context->getRequest());
    }

    public function testGetResponse()
    {
        $this->assertSame($this->response, $this->context->getResponse());
    }

    public function testGetAction()
    {
        $this->assertSame($this->action, $this->context->getAction());
        $this->assertTrue($this->context->isAction($this->action));
    }

    public function testValuesAccessor()
    {
        $this->assertTrue($this->context->has('testKey'));
        $this->assertEquals('testValue', $this->context->get('testKey'));

        $this->assertEquals('testDefaultValue', $this->context->get('notExistingOne', 'testDefaultValue'));

        $this->context->set('testSecondKey', 'testSecondValue');
        $this->assertEquals('testSecondValue', $this->context->get('testSecondKey'));
    }
}
