<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2013 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Filter;

use DotsUnited\Cabinet\TestCase;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * @covers  DotsUnited\Cabinet\Filter\CallbackFilter
 */
class CallbackFilterTest extends TestCase
{
    public function testCallback()
    {
        $called = false;
        $callback = function() use (&$called) {
            $called = true;

            return 'bar';
        };

        $filter = new CallbackFilter($callback);
        $result = $filter->filter('foo');

        $this->assertTrue($called);
        $this->assertEquals('bar', $result);
    }
}
