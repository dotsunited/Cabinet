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
        $s3Client = $this->getMockBuilder('Aws\S3\S3Client')
                         ->disableOriginalConstructor()
                         ->setMethods(array('putObject', 'getObject', 'headObject', 'copyObject', 'deleteObject', 'doesObjectExist', 'getPresignedUrl', 'get'))
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
            's3_client'          => $s3Client,
            'bucket'             => 'testbucket',
            'mime_type_detector' => $mimeTypeDetector,
            'filename_filter'    => $filenameFilter
        );

        $adapter = new AmazonS3Adapter($config);

        return $adapter;
    }

    private function getResponse(array $headers = array(), $body = '', $status = 200)
    {
        $data = array(
            'Body' => \Guzzle\Http\EntityBody::factory($body)
        );

        foreach ($headers as $header => $value) {
            $data[str_replace('-', '', $header)] = $value;
        }

        return new \Guzzle\Service\Resource\Model($data);
    }

    /**************************************************************************/

    public function testDefaultConfig()
    {
        $s3Client = $this->getMockBuilder('Aws\S3\S3Client')
                         ->disableOriginalConstructor()
                         ->getMock();

        $adapter = new AmazonS3Adapter(array(
            's3_client' => $s3Client,
        ));

        $this->assertInstanceOf('Aws\S3\S3Client', $adapter->getS3Client());
        $this->assertNull($adapter->getBucket());
        $this->assertEquals(\Aws\S3\Enum\StorageClass::STANDARD, $adapter->getStorageClass());
        $this->assertEquals(\Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS, $adapter->getAcl());
        $this->assertSame(0, $adapter->getUriExpirationTime());
        $this->assertInstanceOf('DotsUnited\Cabinet\MimeType\Detector\FileinfoDetector', $adapter->getMimeTypeDetector());
        $this->assertNull($adapter->getFilenameFilter());
    }

    public function testConstructorAcceptsConfig()
    {
        $s3Client = $this->getMockBuilder('Aws\S3\S3Client')
                         ->disableOriginalConstructor()
                         ->getMock();

        $detectorMock = $this->getMockBuilder('DotsUnited\Cabinet\MimeType\Detector\DetectorInterface')
                             ->getMock();

        $filterMock = $this->getMockBuilder('DotsUnited\Cabinet\Filter\FilterInterface')
                           ->getMock();

        $config = array(
            's3_client'           => $s3Client,
            'bucket'              => 'testbucket',
            'storage_class'       => \Aws\S3\Enum\StorageClass::REDUCED_REDUNDANCY,
            'acl'                 => \Aws\S3\Enum\CannedAcl::PUBLIC_READ,
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

    /**************************************************************************/

    public function testImport()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $external = fopen(__DIR__ . '/_files/test.txt', 'r');

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testImport.txt',
            'Body'         => $external,
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'ContentType' => 'text/plain'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->import($external, 'subdir/testImport.txt');
        $this->assertTrue($return);
    }

    public function testImportCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/testImport.txt');
    }

    public function testWriteString()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testWriteString.txt',
            'Body'         => 'somedata',
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'ContentType' => 'text/plain'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->with($params)
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

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testWriteResource.txt',
            'Body'         => $resource,
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'ContentType' => 'text/plain'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->write('subdir/testWriteResource.txt', $resource);
        $this->assertTrue($return);
    }

    public function testWriteArray()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testWriteArray.txt',
            'Body'         => 'somedata',
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'ContentType' => 'text/plain'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->write('subdir/testWriteArray.txt', array('s', 'o', 'm', 'e', 'd', 'a', 't', 'a'));
        $this->assertTrue($return);
    }

    public function testWriteCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('putObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->write('subdir/testWriteArray.txt', 'somedata');
    }

    public function testRead()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getObject')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->read('subdir/test.txt');

        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_STRING, $return);
    }

    public function testReadCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->read('subdir/test.txt');
    }

    public function testStream()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getObject')
            ->withAnyParameters()
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->stream('subdir/test.txt');
        $this->assertInternalType(\PHPUnit_Framework_Constraint_IsType::TYPE_RESOURCE, $return);
    }

    public function testStreamCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->stream('subdir/test.txt');
    }

    public function testCopy()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testCopy_copy.txt',
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'CopySource'   => urlencode('testbucket/subdir/testCopy.txt')
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('copyObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');
        $this->assertTrue($return);
    }

    public function testCopyCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('copyObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');
    }

    public function testRename()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket'       => 'testbucket',
            'Key'          => 'subdir/testRename_rename.txt',
            'ACL'          => \Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS,
            'StorageClass' => \Aws\S3\Enum\StorageClass::STANDARD,
            'CopySource'   => urlencode('testbucket/subdir/testRename.txt')
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('copyObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testRename.txt',
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('deleteObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');
        $this->assertTrue($return);
    }

    public function testRenameCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('copyObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');
    }

    public function testUnlink()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testUnlink.txt'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('deleteObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse()));

        $return = $adapter->unlink('subdir/testUnlink.txt');
        $this->assertTrue($return);
    }

    public function testUnlinkCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('deleteObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->unlink('subdir/testUnlink.txt');
    }

    public function testExists()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('doesObjectExist')
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

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('doesObjectExist')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->exists('subdir/testExists.txt');
    }

    public function testSize()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testSize.txt'
        );

        $headers = array(
            'Content-Length' => '8'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse($headers, '', 200)));

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertEquals(8, $return);
    }

    public function testSizeReturnsFalseIfNoContentTypeHeaderPresent()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testSize.txt'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse(array(), '', 200)));

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertFalse($return);
    }

    public function testSizeCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->size('subdir/testSize.txt');
    }

    public function testType()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testType.txt'
        );

        $headers = array(
            'Content-Type' => 'text/plain'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse($headers)));

        $return = $adapter->type('subdir/testType.txt');
        $this->assertEquals('text/plain', $return);
    }

    public function testTypeReturnsNullIfNoContentTypeHeaderPresent()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $params = array(
            'Bucket' => 'testbucket',
            'Key'    => 'subdir/testType.txt'
        );

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->with($params)
            ->will($this->returnValue($this->getResponse(array(), '', 200)));

        $return = $adapter->type('subdir/testType.txt');
        $this->assertNull($return);
    }

    public function testTypeCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('headObject')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->type('subdir/testType.txt');
    }

    public function testUri()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $request = $this->getMock('Guzzle\Http\Message\RequestInterface');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('get')
            ->with('testbucket/subdir/testUri.txt')
            ->will($this->returnValue($request));

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getPresignedUrl', 0)
            ->with($request)
            ->will($this->returnValue('http://test/subdir/testUri.txt'));

        $return = $adapter->uri('subdir/testUri.txt');

        $this->assertEquals('http://test/subdir/testUri.txt', $return);
    }

    public function testUriCatchesAmazonS3Exception()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $this->setExpectedException('\RuntimeException', 'Exception thrown by Aws\S3\S3Client: Test');

        $adapter->getS3Client()
            ->expects($this->once())
            ->method('getPresignedUrl')
            ->withAnyParameters()
            ->will($this->throwException(new \Aws\S3\Exception\S3Exception('Test')));

        $adapter->uri('subdir/testUri.txt');
    }
}
