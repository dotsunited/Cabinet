<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Adapter;

use DotsUnited\Cabinet\Filter\HashedSubpath;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 *
 * @covers  DotsUnited\Cabinet\Adapter\Stream
 * @covers  DotsUnited\Cabinet\Filter\HashedSubpath
 */
class StreamHashedSubpathIntegrationTest extends \PHPUnit_Framework_TestCase
{
    protected function setupAdapter()
    {
        if (!class_exists('\vfsStream', false)) {
            if (false !== ($fp = @fopen('vfsStream/vfsStream.php', 'r', true))) {
                @fclose($fp);
                require_once 'vfsStream/vfsStream.php';
            }
        }

        if (!class_exists('\vfsStream', false)) {
            $this->markTestSkipped(
                'vfsStream is not available. See: http://code.google.com/p/bovigo/wiki/vfsStreamDocsInstall'
            );

            return false;
        }

        $filter = new HashedSubpath();
        $filter
            ->setLevel(4)
            ->setPreserveDirs(true);

        \vfsStream::setup('StreamHashedSubpathIntegrationTest');
        $adapter = new Stream();
        $adapter->setBasePath(\vfsStream::url('StreamHashedSubpathIntegrationTest'));
        $adapter->setFilenameFilter($filter);

        return $adapter;
    }

    /**************************************************************************/

    public function testImport()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/test.txt');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
    }

    public function testWriteString()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', 'somedata');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/d/d/1/8/test.txt')->getContent());
    }

    public function testWriteResource()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', fopen(__DIR__ . '/_files/test.txt', 'r'));

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/d/d/1/8/test.txt')->getContent());
    }

    public function testWriteArray()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', array('s', 'o', 'm', 'e', 'd', 'a', 't', 'a'));

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/d/d/1/8/test.txt')->getContent());
    }

    public function testRead()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->read('subdir/test.txt');

        $this->assertSame('somedata', $return);
    }

    public function testStream()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->stream('subdir/test.txt');

        $this->assertTrue(is_resource($return));
    }

    public function testCopy()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->copy('subdir/test.txt', 'subdir/test_copy.txt');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/d/8/3/d/test_copy.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/d/8/3/d/test_copy.txt')->getContent());

        $this->assertTrue($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
    }

    public function testRename()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->rename('subdir/test.txt', 'subdir_rename/test_rename.txt');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);

        $this->assertTrue($vfsRoot->hasChild('subdir_rename/5/f/a/f/test_rename.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir_rename/5/f/a/f/test_rename.txt')->getContent());

        $this->assertFalse($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
    }

    public function testUnlink()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $return = $adapter->unlink('subdir/test.txt');

        $this->assertFalse($return);

        $this->testWriteString();

        $return = $adapter->unlink('subdir/test.txt');

        $this->assertTrue($return);
        $this->assertFalse($vfsRoot->hasChild('subdir/d/d/1/8/test.txt'));
    }

    public function testExists()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->exists('subdir/test.txt');

        $this->assertTrue($return);
    }

    public function testSize()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->testWriteString();

        $return = $adapter->size('subdir/test.txt');

        $this->assertEquals(8, $return);
    }

    public function testType()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        // Fileinfo doesn't seem to work with vfsStream
        $mimeTypeDetector = $this->getMockBuilder('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface')
                                 ->getMock();

        $mimeTypeDetector
            ->expects($this->any())
            ->method('detectFromFile')
            ->will($this->returnValue('text/plain'));

        $adapter->setMimeTypeDetector($mimeTypeDetector);

        $this->testWriteString();

        $return = $adapter->type('test.txt');

        $this->assertEquals('text/plain', $return);
    }
}
