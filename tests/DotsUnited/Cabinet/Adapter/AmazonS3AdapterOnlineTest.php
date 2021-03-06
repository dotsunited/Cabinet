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

use Aws\S3\S3Client;
use DotsUnited\Cabinet\TestCase;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * @covers  DotsUnited\Cabinet\Adapter\AmazonS3Adapter
 */
class AmazonS3AdapterOnlineTest extends TestCase
{
    private $s3Client;

    private function setupAdapter($invalid = false)
    {
        $bucket = constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_BUCKET_NAME');

        if ($invalid) {
            $s3Client = S3Client::factory(array(
                'credentials' => array(
                    'key'    => 'foo',
                    'secret' => 'bar',
                ),
                'region'  => 'us-east-1',
                'version' => '2006-03-01'
            ));
        } else {
            $key    = constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_AWS_KEY');
            $secret = constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_AWS_SECRET_KEY');
            $region = constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_BUCKET_REGION');

            $this->s3Client = $s3Client = S3Client::factory(array(
                'credentials' => array(
                    'key'    => $key,
                    'secret' => $secret,
                ),
                'region'  => $region,
                'version' => '2006-03-01'
            ));

            if (!$s3Client->doesBucketExist($bucket)) {
                $s3Client->createBucket(array(
                    'Bucket' => $bucket,
                    'LocationConstraint' => $region
                ));

                // Wait until the bucket is created
                $s3Client->waitUntil('BucketExists', array('Bucket' => $bucket));
            } else {
                $s3Client->clearBucket($bucket);
            }
        }

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

        $config = array(
            's3_client'          => $s3Client,
            'bucket'             => $bucket,
            'mime_type_detector' => $mimeTypeDetector,
            'throw_exceptions'   => !$invalid
        );

        $adapter = new AmazonS3Adapter($config);

        return $adapter;
    }

    public function setUp()
    {
        if (!constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_ENABLED') || constant('TESTS_DOTSUNITED_CABINET_ADAPTER_AMAZONS3_ONLINE_ENABLED') != 'true') {
            $this->markTestSkipped('DotsUnited\Cabinet\Adapter\AmazonS3Adapter online tests are not enabled');
        }
    }

    /**************************************************************************/

    public function testImport()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/testImport.txt');

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testImport.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testImport.txt'));
    }

    public function testImportFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->import(__DIR__ . '/_files/test.txt', 'subdir/testImport.txt');
        $this->assertFalse($return);
    }

    public function testWriteString()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/testWriteString.txt', 'somedata');

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testWriteString.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testWriteString.txt'));
    }

    public function testWriteResource()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/testWriteResource.txt', fopen(__DIR__ . '/_files/test.txt', 'rb'));

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testWriteResource.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testWriteResource.txt'));
    }

    public function testWriteArray()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->write('subdir/testWriteArray.txt', array('s', 'o', 'm', 'e', 'd', 'a', 't', 'a'));

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testWriteArray.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testWriteArray.txt'));
    }

    public function testWriteFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->write('subdir/testWriteString.txt', 'somedata');
        $this->assertFalse($return);
    }

    public function testRead()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testRead.txt', 'somedata');

        $return = $adapter->read('subdir/testRead.txt');

        $this->assertSame('somedata', $return);
    }

    public function testReadFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->read('subdir/testRead.txt');
        $this->assertFalse($return);
    }

    public function testStream()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testStream.txt', 'somedata');

        $return = $adapter->stream('subdir/testStream.txt');

        $this->assertInternalType('resource', $return);
    }

    public function testStreamFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->stream('subdir/testStream.txt');
        $this->assertFalse($return);
    }

    public function testCopy()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testCopy.txt', 'somedata');

        $return = $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testCopy_copy.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testCopy_copy.txt'));
    }

    public function testCopyFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->copy('subdir/testCopy.txt', 'subdir/testCopy_copy.txt');
        $this->assertFalse($return);
    }

    public function testRename()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testRename.txt', 'somedata');

        $return = $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');

        $this->assertTrue($return);
        $this->assertTrue($adapter->exists('subdir/testRename_rename.txt'));
        $this->assertEquals('somedata', $adapter->read('subdir/testRename_rename.txt'));

        $this->assertFalse($adapter->exists('subdir/testRename.txt'));
    }

    public function testRenameFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->rename('subdir/testRename.txt', 'subdir/testRename_rename.txt');
        $this->assertFalse($return);
    }

    public function testUnlink()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testUnlink.txt', 'somedata');

        $return = $adapter->unlink('subdir/testUnlink.txt');

        $this->assertTrue($return);
        $this->assertFalse($adapter->exists('subdir/testUnlink.txt'));
    }

    public function testUnlinkFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->unlink('subdir/testUnlink.txt');
        $this->assertFalse($return);
    }

    public function testExists()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $return = $adapter->exists('subdir/testExists.txt');

        $this->assertFalse($return);

        $adapter->write('subdir/testExists.txt', 'somedata');

        $return = $adapter->exists('subdir/testExists.txt');

        $this->assertTrue($return);
    }

    public function testExistsFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->exists('subdir/testExists.txt');
        $this->assertFalse($return);
    }

    public function testSize()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testSize.txt', 'somedata');

        $return = $adapter->size('subdir/testSize.txt');

        $this->assertEquals(8, $return);
    }

    public function testSizeFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->size('subdir/testSize.txt');
        $this->assertFalse($return);
    }

    public function testType()
    {
        if (!$adapter = $this->setupAdapter()) {
            return;
        }

        $adapter->write('subdir/testType.txt', 'somedata');

        $return = $adapter->type('subdir/testType.txt');

        $this->assertEquals('text/plain', $return);
    }

    public function testTypeFail()
    {
        if (!$adapter = $this->setupAdapter(true)) {
            return;
        }

        $return = $adapter->type('subdir/testType.txt');
        $this->assertNull($return);
    }
}
