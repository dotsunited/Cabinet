<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 *
 * @covers  DotsUnited\Cabinet\Cabinet
 */
class CabinetTest extends \PHPUnit_Framework_TestCase
{
    public function testFactoryAdapterClassName()
    {
        $adapter = Cabinet::factory('DotsUnited\Cabinet\Adapter\StreamAdapter');

        $this->assertType('DotsUnited\Cabinet\Adapter\AdapterInterface', $adapter);
        $this->assertTrue(class_exists('DotsUnited\Cabinet\Adapter\StreamAdapter'));
        $this->assertType('DotsUnited\Cabinet\Adapter\StreamAdapter', $adapter);
    }

    public function testFactoryCustomAdapter()
    {
        $adapter = Cabinet::factory('DotsUnited\Cabinet\TestAsset\TestAdapter');

        $this->assertType('DotsUnited\Cabinet\Adapter\AdapterInterface', $adapter);
        $this->assertTrue(class_exists('DotsUnited\Cabinet\TestAsset\TestAdapter'));
        $this->assertType('DotsUnited\Cabinet\TestAsset\TestAdapter', $adapter);
    }

    public function testFactoryExceptionInvalidAdapter()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Adapter class "DotsUnited\Cabinet\TestAsset\TestClass" does not implement DotsUnited\Cabinet\Adapter\Adapter'
        );

        Cabinet::factory('DotsUnited\Cabinet\TestAsset\TestClass');
    }

    public function testFactoryExceptionInvalidAdapterClassName()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Adapter name must be specified in a string'
        );

        Cabinet::factory(null);
    }

    public function testFactoryArray()
    {
        $config = array(
            'adapter' => 'DotsUnited\Cabinet\TestAsset\TestAdapter',
            'config' => array(
                'test' => 'dummy'
            )
        );
        $adapter = Cabinet::factory($config);
        $this->assertType('DotsUnited\Cabinet\TestAsset\TestAdapter', $adapter);
        $this->assertEquals('dummy', $adapter->config['test']);
    }

    public function testFactoryArrayExceptionNoAdapter()
    {
        $this->setExpectedException(
            '\InvalidArgumentException',
            'Adapter name must be specified in a string'
        );

        $config = array(
            'config' => array(
                'test' => 'dummy'
            )
        );

        Cabinet::factory($config);
    }

    public function testFactoryArrayOverrideArray()
    {
        $config1 = array(
            'adapter' => 'DotsUnited\Cabinet\TestAsset\TestAdapter',
            'config' => array(
                'test' => 'dummy'
            )
        );
        $config2 = array(
            'test' => 'vanilla'
        );

        $adapter = Cabinet::factory($config1, $config2);
        $this->assertType('DotsUnited\Cabinet\TestAsset\TestAdapter', $adapter);
        // second arg should be ignored
        $this->assertEquals('dummy', $adapter->config['test']);
    }
}
