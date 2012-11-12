<?php
namespace Microsite;

/**
 * A simple custom Handler class
 */
class TestHandler extends \Microsite\Handler
{
	public $stored_value;

	public function handler_one()
	{
		return 'test';
	}

	public function store_value()
	{
		$this->stored_value = 'test';
	}

	public function handler_two()
	{
		return $this->stored_value;
	}
}

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2012-11-12 at 02:10:29.
 */
class HandlerTest extends \PHPUnit_Framework_TestCase
{
	/**
	 * @covers Microsite\Handler::handle
	 */
	public function testHandle()
	{
		$one = TestHandler::handle('\Microsite\TestHandler', 'handler_one');

		$this->assertEquals('test', $one(new Response([]), new Request([]), new App()));

		$store = TestHandler::handle('\Microsite\TestHandler', 'store_value');
		$two = TestHandler::handle('\Microsite\TestHandler', 'handler_two');

		$store(new Response([]), new Request([]), new App());
		$this->assertEquals('test', $two(new Response([]), new Request([]), new App()));
	}
}
