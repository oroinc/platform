<?php

namespace Oro\Bundle\SoapBundle\Tests\Unit\Handler;

use Oro\Bundle\SoapBundle\Handler\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContextTest extends \PHPUnit\Framework\TestCase
{
    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /** @var string */
    protected $action;

    /** @var object */
    protected $controllerObject;

    /** @var array */
    protected $values = ['testKey' => 'testValue'];

    /** @var Context */
    protected $context;

    protected function setUp()
    {
        $this->request          = Request::create(uniqid('uri'));
        $this->response         = new Response();
        $this->action           = uniqid('actionName');
        $this->controllerObject = new \stdClass();

        $this->context = new Context(
            $this->controllerObject,
            $this->request,
            $this->response,
            $this->action,
            $this->values
        );
    }

    protected function tearDown()
    {
        unset($this->context, $this->request, $this->response, $this->controllerObject, $this->action);
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
        $this->assertEquals($this->context->get('testKey'), 'testValue');

        $this->assertEquals($this->context->get('notExistingOne', 'testDefaultValue'), 'testDefaultValue');

        $this->context->set('testSecondKey', 'testSecondValue');
        $this->assertEquals($this->context->get('testSecondKey'), 'testSecondValue');
    }
}
