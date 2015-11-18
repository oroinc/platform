<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    /**
     * keys of request headers should be are case insensitive
     */
    public function testRequestHeaders()
    {
        $context = new Context();
        $headers = $context->getRequestHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertTrue($headers->has(strtoupper($key1)));
        $this->assertTrue(isset($headers[strtoupper($key1)]));
        $this->assertEquals($value1, $headers->get(strtoupper($key1)));
        $this->assertEquals($value1, $headers[strtoupper($key1)]);

        $headers->remove(strtoupper($key1));
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[strtoupper($key2)] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has(strtoupper($key2)));
        $this->assertFalse(isset($headers[strtoupper($key2)]));
        $this->assertNull($headers->get(strtoupper($key2)));
        $this->assertNull($headers[strtoupper($key2)]);

        $headers->set(strtoupper($key1), null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
    }

    /**
     * keys of response headers should be are case sensitive
     */
    public function testResponseHeaders()
    {
        $context = new Context();
        $headers = $context->getResponseHeaders();

        $key1   = 'test1';
        $key2   = 'test2';
        $value1 = 'value1';
        $value2 = 'value2';

        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers->set($key1, $value1);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertEquals($value1, $headers->get($key1));
        $this->assertEquals($value1, $headers[$key1]);

        $this->assertFalse($headers->has(strtoupper($key1)));
        $this->assertFalse(isset($headers[strtoupper($key1)]));
        $this->assertNull($headers->get(strtoupper($key1)));
        $this->assertNull($headers[strtoupper($key1)]);
        $headers->remove(strtoupper($key1));
        $this->assertTrue($headers->has($key1));
        unset($headers[strtoupper($key1)]);
        $this->assertTrue($headers->has($key1));

        $headers->remove($key1);
        $this->assertFalse($headers->has($key1));
        $this->assertFalse(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $headers[$key2] = $value2;
        $this->assertTrue($headers->has($key2));
        $this->assertTrue(isset($headers[$key2]));
        $this->assertEquals($value2, $headers->get($key2));
        $this->assertEquals($value2, $headers[$key2]);

        unset($headers[$key2]);
        $this->assertFalse($headers->has($key2));
        $this->assertFalse(isset($headers[$key2]));
        $this->assertNull($headers->get($key2));
        $this->assertNull($headers[$key2]);

        $headers->set($key1, null);
        $this->assertTrue($headers->has($key1));
        $this->assertTrue(isset($headers[$key1]));
        $this->assertNull($headers->get($key1));
        $this->assertNull($headers[$key1]);

        $this->assertEquals(1, count($headers));
        $this->assertEquals([$key1 => null], $headers->toArray());

        $headers->clear();
        $this->assertEquals(0, count($headers));
    }

    public function testVersion()
    {
        $context = new Context();

        $this->assertNull($context->getVersion());

        $context->setVersion('test');
        $this->assertEquals('test', $context->getVersion());
        $this->assertEquals('test', $context->get(Context::VERSION));
    }

    public function testClassName()
    {
        $context = new Context();

        $this->assertNull($context->getClassName());

        $context->setClassName('test');
        $this->assertEquals('test', $context->getClassName());
        $this->assertEquals('test', $context->get(Context::CLASS_NAME));
    }

    public function testQuery()
    {
        $context = new Context();

        $this->assertFalse($context->hasQuery());
        $this->assertNull($context->getQuery());

        $query = new \stdClass();

        $context->setQuery($query);
        $this->assertTrue($context->hasQuery());
        $this->assertSame($query, $context->getQuery());
        $this->assertSame($query, $context->get(Context::QUERY));

        $context->setQuery(null);
        $this->assertTrue($context->hasQuery());
    }

    public function testCriteria()
    {
        $context = new Context();

        $this->assertNull($context->getCriteria());

        $criteria = $this->getMockBuilder('Oro\Bundle\ApiBundle\Util\Criteria')
            ->disableOriginalConstructor()
            ->getMock();

        $context->setCriteria($criteria);
        $this->assertSame($criteria, $context->getCriteria());
        $this->assertSame($criteria, $context->get(Context::CRITERIA));
    }
}
