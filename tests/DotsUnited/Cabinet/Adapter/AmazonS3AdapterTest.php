<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2013 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Adapter;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * @covers  DotsUnited\Cabinet\Adapter\AmazonS3Adapter
 */
class AmazonS3AdapterTest extends \PHPUnit_Framework_TestCase
{
    private function setupAdapter()
    {
        if (!class_exists('\CFRuntime')) {
            $this->markTestSkipped(
                'AWS SDK for PHP is not available. Install dev requirements with "php composer.phar install --dev".'
            );

            return false;
        }

        $amazonS3 = $this->getMockBuilder('\AmazonS3')
                         ->disableOriginalConstructor()
                         ->getMock();

        $mimeTypeDetector = $this->getMockBuilder('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface')
                                 ->getMock();

        $mimeTypeDetector
            ->expects($this->any())
            ->method('detectFromFile')
            ->will($this->returnValue('text/plain'));

        $mimeTypeDetector
            ->expects($this->any())
            ->method('detectFromString')
            ->will($this->returnValue('text/plain'));

        $mimeTypeDetector
            ->expects($this->any())
            ->method('detectFromResource')
            ->will($this->returnValue('text/plain'));

        $filenameFilter = $this->getMockBuilder('DotsUnited\Cabinet\Filter\FilterInterface')
                               ->getMock();
        $filenameFilter
            ->expects($this->any())
            ->method('filter')
            ->withAnyParameters()
            ->will($this->returnArgument(0));

        $config = array(
            'amazon_s3'          => $amazonS3,
            'bucket'             => 'testbucket',
            'mime_type_detector' => $mimeTypeDetector,
            'filename_filter'    => $filenameFilter
        );

        $adapter = new AmazonS3Adapter($config);

        return $adapter;
    }

    private function getResponse(array $header = array(), $body = '', $status = 200)
    {
        return new \CFResponse($header, $body, $status);

        /*$response = $this->getMockBuilder('\CFResponse')
                         ->disableOriginalConstructor()
                         ->getMock();
        $response
            ->expects($this->once())
            ->method('isOk')
            ->withAnyParameters()
            ->will($this->returnValue(true));

        return $response;*/
    }

    /**************************************************************************/

    public function testDefaultConfig()
    {
        $adapter = new AmazonS3Adapter(array(
            'aws_key'        => 'foo',
            'aws_secret_key' => 'bar'
        ));

        $this->assertInstanceOf('\AmazonS3', $adapter->getAmazonS3());
        $this->assertNull($adapter->getBucket());
        $this->assertEquals(\AmazonS3::STORAGE_STANDARD, $adapter->getStorageClass());
        $this->assertEquals(\AmazonS3::ACL_PRIVATE, $adapter->getAcl());
        $this->assertSame(0, $adapter->getUriExpirationTime());
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
            'aws_key'             => 'foo',
            'aws_secret_key'      => 'bar',
            'bucket'              => 'testbucket',
            'storage_class'       => \AmazonS3::STORAGE_REDUCED,
            'acl'                 => \AmazonS3::ACL_PUBLIC,
            'uri_expiration_time' => 12345,
            'mime_type_detector'  => $detectorMock,
            'filename_filter'     => $filterMock
        );

        $adapter = new AmazonS3Adapter($config);

        $this->assertEquals($config['bucket'], $adapter->getBucket());
        $this->assertEquals($config['storage_class'], $adapter->getStorageClass());
        $this->assertEquals($config['acl'], $adapter->getAcl());
        $this->assertEquals($config['uri_expiration_time'], $adapter->getUriExpirationTime());
        $this->assertEquals($config['mime_type_detector'], $adapter->getMimeTypeDetector());
        $this->assertInstanceOf('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface', $adapter->getMimeTypeDetector());
        $this->assertEquals($config['filename_filter'], $adapter->getFilenameFilter());
        $this->assertInstanceOf('DotsUnited\Cabinet\Filter\FilterInterface', $adapter->getFilenameFilter());
    }

    public function testConstructorCatchesAmazonS3Exception()
    {
        $this->setExpectedException('\RuntimeException');

        $config = array(
            'aws_key'        => null,
            'aws_secret_key' => null
        );

        new AmazonS3Adapter($config);
    }

    public function testConstructorPassesAmazonS3ConfigAsArrayToInstance()
    {
        $config = array(
            'aws_key'        => 'foo',
            'aws_secret_key' => 'bar',
            'amazon_s3'      => array(
                'vhost' => 'test.example.com'
            )
        );

        $adapter = new AmazonS3Adapter($config);
        $this->assertEquals('test.example.com', $adapter->getAmazonS3()->vhost);
    }

    /**************************************************************************/

    public function testImport()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $opt = array(
            'fileUpload'  => __DIR__ . '/_files/test.txt',
            'acl'         => \AmazonS3::ACL_PRIVATE,
            'storage'     => \AmazonS3::STORAGE_STANDARD,
            'contentType' => 'text/plain'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->with('testbucket', 'subdir/testImport.txt', $opt)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/testImport.txt');
        $this->assertTrue($return);
    }

    public function testImportCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/testImport.txt');
    }

    public function testWriteString()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $opt = array(
            'acl'         => \AmazonS3::ACL_PRIVATE,
            'storage'     => \AmazonS3::STORAGE_STANDARD,
            'body'        => 'somedata',
            'contentType' => 'text/plain'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->with('testbucket', 'subdir/testWriteString.txt', $opt)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->write('subdir/testWriteString.txt', 'somedata');
        $this->assertTrue($return);
    }

    public function testWriteResource()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $resource = fopen(__DIR__ . '/_files/test.txt', 'rb');

        $opt = array(
            'acl'         => \AmazonS3::ACL_PRIVATE,
            'storage'     => \AmazonS3::STORAGE_STANDARD,
            'fileUpload'  => $resource,
            'contentType' => 'text/plain'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->with('testbucket', 'subdir/testWriteResource.txt', $opt)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->write('subdir/testWriteResource.txt', $resource);
        $this->assertTrue($return);
    }

    public function testWriteArray()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $opt = array(
            'acl'         => \AmazonS3::ACL_PRIVATE,
            'storage'     => \AmazonS3::STORAGE_STANDARD,
            'body'        => 'somedata',
            'contentType' => 'text/plain'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->with('testbucket', 'subdir/testWriteArray.txt', $opt)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->write('subdir/testWriteArray.txt', array('s', 'o', 'm', 'e', 'd', 'a', 't', 'a'));
        $this->assertTrue($return);
    }

    public function testWriteCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('create_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->write('subdir/testWriteArray.txt', 'somedata');
    }

    public function testRead()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->read('subdir/test.txt');

        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
    }

    public function testReadReturnsFalseIfResponseIsNotOk()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse(array(), '', 404)));

        $return = $adapter->read('subdir/test.txt');
        $this->assertFalse($return);
    }

    public function testReadCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->read('subdir/test.txt');
    }

    public function testStream()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->stream('subdir/test.txt');
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $return);
    }

    public function testStreamReturnsFalseIfResponseIsNotOk()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse(array(), '', 404)));

        $return = $adapter->stream('subdir/test.txt');
        $this->assertFalse($return);
    }

    public function testStreamCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->stream('subdir/test.txt');
    }

    public function testCopy()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $src = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testCopy.txt'
        );

        $dest = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testCopy_copy.txt'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('copy_object')
            ->with($src, $dest)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');
        $this->assertTrue($return);
    }

    public function testCopyCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('copy_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');
    }

    public function testRename()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $src = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testRename.txt'
        );

        $dest = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testRename_rename.txt'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('copy_object')
            ->with($src, $dest)
            ->will($this->returnValue($this->getResponse()));

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('delete_object')
            ->with('testbucket', 'subdir/testRename.txt')
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');
        $this->assertTrue($return);
    }

    public function testRenameReturnsFalseIfCopyFails()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $src = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testRename.txt'
        );

        $dest = array(
            'bucket'   => 'testbucket',
            'filename' => 'subdir/testRename_rename.txt'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('copy_object')
            ->with($src, $dest)
            ->will($this->returnValue($this->getResponse(array(), '', 404)));

        $adapter->getAmazonS3()
            ->expects($this->never())
            ->method('delete_object');

        $return = $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');
        $this->assertFalse($return);
    }

    public function testUnlink()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('delete_object')
            ->with('testbucket', 'subdir/testUnlink.txt')
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->unlink('subdir/testUnlink.txt');
        $this->assertTrue($return);
    }

    public function testUnlinkCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('delete_object')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->unlink('subdir/testUnlink.txt');
    }

    public function testExists()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('if_object_exists')
            ->with('testbucket', 'subdir/testExists.txt')
            ->will($this->returnValue(true));

        $return = $adapter->exists('subdir/testExists.txt');
        $this->assertTrue($return);
    }

    public function testExistsCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('if_object_exists')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->exists('subdir/testExists.txt');
    }

    public function testSize()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $headers = array(
            'Content-Length' => '8'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testSize.txt')
            ->will($this->returnValue($this->getResponse($headers)));

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertEquals(8, $return);
    }

    public function testSizeReturnsFalseIfNoContentTypeHeaderPresent()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testSize.txt')
            ->will($this->returnValue($this->getResponse(array())));

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertFalse($return);
    }

    public function testSizeReturnsFalseIfResponseIsNotOk()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testSize.txt')
            ->will($this->returnValue($this->getResponse(array(), '', 404)));

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertFalse($return);
    }

    public function testSizeCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->size('subdir/testSize.txt');
    }

    public function testType()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $headers = array(
            'Content-Type' => 'text/plain'
        );

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testType.txt')
            ->will($this->returnValue($this->getResponse($headers)));

        $return = $adapter->type('subdir/testType.txt');
        $this->assertEquals('text/plain', $return);
    }

    public function testTypeReturnsNullIfNoContentTypeHeaderPresent()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testType.txt')
            ->will($this->returnValue($this->getResponse(array())));

        $return = $adapter->type('subdir/testType.txt');
        $this->assertNull($return);
    }

    public function testTypeReturnsNullIfResponseIsNotOk()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->with('testbucket', 'subdir/testType.txt')
            ->will($this->returnValue($this->getResponse(array(), '', 404)));

        $return = $adapter->type('subdir/testType.txt');
        $this->assertNull($return);
    }

    public function testTypeCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_headers')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->type('subdir/testType.txt');
    }

    public function testUri()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_url')
            ->with('testbucket', 'subdir/testUri.txt', 0)
            ->will($this->returnValue('http://test/subdir/testUri.txt'));

        $return = $adapter->uri('subdir/testUri.txt');

        $this->assertEquals('http://test/subdir/testUri.txt', $return);
    }

    public function testUriCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by \AmazonS3: Test');

        $adapter->getAmazonS3()
            ->expects($this->once())
            ->method('get_object_url')
            ->withAnyParameters()
            ->will($this->throwException(new \S3_Exception('Test')));

        $adapter->uri('subdir/testUri.txt');
    }
}
