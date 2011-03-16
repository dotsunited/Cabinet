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

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 *
 * @covers  DotsUnited\Cabinet\Adapter\StreamAdapter
 */
class StreamAdapterTest extends \PHPUnit_Framework_TestCase
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

        $mimeTypeDetector = $this->getMockBuilder('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface')
                                 ->getMock();

        $mimeTypeDetector
            ->expects($this->any())
            ->method('detectFromFile')
            ->will($this->returnValue('text/plain'));

        \vfsStream::setup('StreamTest');
        $adapter = new StreamAdapter();
        $adapter->setBasePath(\vfsStream::url('StreamTest'));
        $adapter->setMimeTypeDetector($mimeTypeDetector);

        return $adapter;
    }

    /**************************************************************************/

    public function testDefaultConfig()
    {
        $adapter = new StreamAdapter();

        $this->assertNull($adapter->getBasePath());
        $this->assertNull($adapter->getBaseUri());
        $this->assertEquals(0700, $adapter->getDirectoryUmask());
        $this->assertEquals(0600, $adapter->getFileUmask());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $adapter->getStreamContext());
        $this->assertInstanceOf('DotsUnited\Cabinet\MimeType\Detector\FileinfoDetector', $adapter->getMimeTypeDetector());
        $this->assertNull($adapter->getFilenameFilter());
    }

    public function testConstructorAcceptsConfig()
    {
        $detectorMock = $this->getMockBuilder('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface')
                             ->getMock();

        $filterMock = $this->getMockBuilder('DotsUnited\Cabinet\Filter\FilterInterface')
                           ->getMock();

        $config = array(
            'base_path'          => '/path/to/base',
            'base_uri'           => 'cabinet://',
            'directory_umask'    => 0777,
            'file_umask'         => 0666,
            'stream_context'     => array(),
            'mime_type_detector' => $detectorMock,
            'filename_filter'    => $filterMock
        );

        $adapter = new StreamAdapter($config);

        $this->assertEquals($config['base_path'], $adapter->getBasePath());
        $this->assertEquals($config['base_uri'], $adapter->getBaseUri());
        $this->assertEquals($config['directory_umask'], $adapter->getDirectoryUmask());
        $this->assertEquals($config['file_umask'], $adapter->getFileUmask());
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $adapter->getStreamContext());
        $this->assertEquals($config['mime_type_detector'], $adapter->getMimeTypeDetector());
        $this->assertInstanceOf('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface', $adapter->getMimeTypeDetector());
        $this->assertEquals($config['filename_filter'], $adapter->getFilenameFilter());
        $this->assertInstanceOf('DotsUnited\Cabinet\Filter\FilterInterface', $adapter->getFilenameFilter());
    }

    public function testSetBasePathTrimsTrailingSlash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBasePath('/path/to/base/');

        $this->assertEquals('/path/to/base', $adapter->getBasePath());
    }

    public function testSetBasePathTrimsTrailingBackslash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBasePath('C:\\Path\\To\\Base\\');

        $this->assertEquals('C:\\Path\\To\\Base', $adapter->getBasePath());
    }

    public function testSetBasePathDoesNotTrimTrailingSlashIfEndsWithDoublepointDoubleslash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBasePath('proto://');

        $this->assertEquals('proto://', $adapter->getBasePath());
    }

    public function testSetBaseUriTrimsTrailingSlash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBaseUri('/path/to/base/');

        $this->assertEquals('/path/to/base', $adapter->getBaseUri());
    }

    public function testSetBaseUriTrimsTrailingBackslash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBaseUri('C:\\Path\\To\\Base\\');

        $this->assertEquals('C:\\Path\\To\\Base', $adapter->getBaseUri());
    }

    public function testSetBaseUriDoesNotTrimTrailingSlashIfEndsWithDoublepointDoubleslash()
    {
        $adapter = new StreamAdapter();
        $adapter->setBaseUri('proto://');

        $this->assertEquals('proto://', $adapter->getBaseUri());
    }

    public function testSetDirectoryUmaskConvertsString()
    {
        $adapter = new StreamAdapter();
        $adapter->setDirectoryUmask('0777');

        $this->assertSame(0777, $adapter->getDirectoryUmask());
    }

    public function testSetFileUmaskConvertsString()
    {
        $adapter = new StreamAdapter();
        $adapter->setFileUmask('0666');

        $this->assertSame(0666, $adapter->getFileUmask());
    }

    /**************************************************************************/

    public function testImport()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/test.txt');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/test.txt'));
    }

    public function testWriteString()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', 'somedata');

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/test.txt')->getContent());
    }

    public function testWriteResource()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', fopen(__DIR__ . '/_files/test.txt', 'r'));

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/test.txt')->getContent());
    }

    public function testWriteArray()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/test.txt', array('s', 'o', 'm', 'e', 'd', 'a', 't', 'a'));

        $vfsRoot = \vfsStreamWrapper::getRoot();

        $this->assertTrue($return);
        $this->assertTrue($vfsRoot->hasChild('subdir/test.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/test.txt')->getContent());
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
        $this->assertTrue($vfsRoot->hasChild('subdir/test_copy.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir/test_copy.txt')->getContent());

        $this->assertTrue($vfsRoot->hasChild('subdir/test.txt'));
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
        $this->assertTrue($vfsRoot->hasChild('subdir_rename/test_rename.txt'));
        $this->assertSame('somedata', $vfsRoot->getChild('subdir_rename/test_rename.txt')->getContent());

        $this->assertFalse($vfsRoot->hasChild('subdir/test.txt'));
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
        $this->assertFalse($vfsRoot->hasChild('subdir/test.txt'));
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

        $this->testWriteString();

        $return = $adapter->type('test.txt');

        $this->assertEquals('text/plain', $return);
    }

    public function testUri()
    {
        $adapter = new StreamAdapter();
        $adapter->setBaseUri('cabinet://base/uri');

        $filenameFilter = $this->getMockBuilder('DotsUnited\Cabinet\Filter\FilterInterface')
                               ->getMock();
        $filenameFilter
            ->expects($this->once())
            ->method('filter')
            ->with('test.txt')
            ->will($this->returnArgument(0));

        $adapter->setFilenameFilter($filenameFilter);

        $return = $adapter->uri('test.txt');

        $this->assertEquals('cabinet://base/uri/test.txt', $return);
    }

    public function testPath()
    {
        $adapter = new StreamAdapter();
        $adapter->setBasePath('c:\test');

        $filenameFilter = $this->getMockBuilder('DotsUnited\Cabinet\Filter\FilterInterface')
                               ->getMock();
        $filenameFilter
            ->expects($this->once())
            ->method('filter')
            ->with('test.txt')
            ->will($this->returnArgument(0));

        $adapter->setFilenameFilter($filenameFilter);

        $return = $adapter->path('test.txt');

        $expected = 'c:\test' . DIRECTORY_SEPARATOR . 'test.txt';

        $this->assertEquals($expected, $return);
    }

    public function testPathUsesSlashIfBasePathContainsProtocol()
    {
        $adapter = new StreamAdapter();
        $adapter->setBasePath('cabinet://base/uri');

        $return = $adapter->path('test.txt');

        $this->assertEquals('cabinet://base/uri/test.txt', $return);
    }
}
