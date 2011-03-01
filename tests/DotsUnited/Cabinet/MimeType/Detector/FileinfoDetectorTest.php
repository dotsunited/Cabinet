<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\MimeType\Detector;

/**
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 *
 * @covers  DotsUnited\Cabinet\MimeType\Detector\FileinfoDetector
 */
class FileinfoDetectorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DotsUnited\Cabinet\MimeType\Detector\FileinfoDetector
     */
    protected $detector;

    public function setUp()
    {
        if (!extension_loaded('fileinfo')) {
            $this->markTestSkipped(
                'The "fileinfo" extension is not available.'
            );
        }

        $this->detector = new FileinfoDetector(__DIR__ . '/_files/magic');
    }

    public function tearDown()
    {
        $this->detector = null;
    }

    public static function fileList()
    {
        return array(
            array('test.bmp', 'image/x-ms-bmp'),
            array('test.gif', 'image/gif'),
            array('test.jpg', 'image/jpeg'),
            array('test.pdf', 'application/pdf'),
            array('test.png', 'image/png'),
            array('test.txt', 'text/plain')
        );
    }

    /**
     * @dataProvider fileList
     */
    public function testDetectFromFile($file, $type)
    {
        $this->assertContains($this->detector->detectFromFile(__DIR__ . '/_files/' . $file), (array) $type, 'File "' . $file . '".');
    }

    /**
     * @dataProvider fileList
     */
    public function testDetectFromString($file, $type)
    {
        $this->assertContains($this->detector->detectFromString(file_get_contents(__DIR__ . '/_files/' . $file)), (array) $type, 'File "' . $file . '".');
    }

    /**
     * @dataProvider fileList
     */
    public function testDetectFromResource($file, $type)
    {
        $this->assertContains($this->detector->detectFromResource(fopen(__DIR__ . '/_files/' . $file, 'rb')), (array) $type, 'File "' . $file . '".');
    }

    public function testDetectFromResourceFail()
    {
        $this->assertNull($this->detector->detectFromResource(fopen('php://memory', 'rb')));
    }
}
