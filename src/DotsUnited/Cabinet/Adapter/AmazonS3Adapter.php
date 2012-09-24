<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2012 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Adapter;

use DotsUnited\Cabinet\Filter\FilterInterface;
use DotsUnited\Cabinet\MimeType\Detector\DetectorInterface;
use DotsUnited\Cabinet\MimeType\Detector\FileinfoDetector;

/**
 * DotsUnited\Cabinet\Adapter\AmazonS3Adapter
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 */
class AmazonS3Adapter implements AdapterInterface
{
    /**
     * AmazonS3 class instance.
     *
     * @var \AmazonS3
     */
    private $amazonS3;

    /**
     * The bucket to store file to.
     *
     * @var string
     */
    private $bucket;

    /**
     * The storage class setting for files.
     *
     * Allowed values:
     *   \AmazonS3::STORAGE_STANDARD
     *   \AmazonS3::STORAGE_REDUCED
     *
     * The default value is \AmazonS3::STORAGE_STANDARD.
     *
     * @var string
     */
    private $storageClass;

    /**
     * The ACL settings for files.
     *
     * Allowed values:
     *   \AmazonS3::ACL_PRIVATE
     *   \AmazonS3::ACL_PUBLIC
     *   \AmazonS3::ACL_OPEN
     *   \AmazonS3::ACL_AUTH_READ
     *   \AmazonS3::ACL_OWNER_READ
     *   \AmazonS3::ACL_OWNER_FULL_CONTROL
     *
     * The default value is \AmazonS3::ACL_PRIVATE.
     *
     * @var string
     */
    private $acl;

    /**
     * The expiration time for web-accessible URIs if you store private files.
     *
     * @var string|integer
     */
    private $uriExpirationTime = 0;

    /**
     * The mime type detector.
     *
     * @var \DotsUnited\Cabinet\MimeType\Detector\DetectorInterface
     */
    private $mimeTypeDetector;

    /**
     * The filename filter.
     *
     * @var \DotsUnited\Cabinet\Filter\FilterInterface
     */
    private $filenameFilter;

    /**
     * Constructor.
     *
     * @param  array             $config
     * @throws \RuntimeException
     */
    public function __construct(array $config = array())
    {
        try {
            if (isset($config['amazon_s3'])) {
                if (is_array($config['amazon_s3'])) {
                    $options = $config['amazon_s3'];

                    if (isset($config['aws_key'])) {
                        $options['key'] = $config['aws_key'];
                    }

                    if (isset($config['aws_secret_key'])) {
                        $options['secret'] = $config['aws_secret_key'];
                    }

                    $this->amazonS3 = new \AmazonS3($options);

                    foreach ($config['amazon_s3'] as $key => $val) {
                        if (property_exists($this->amazonS3, $key)) {
                            $this->amazonS3->$key = $val;
                        }
                    }
                } else {
                    $this->setAmazonS3($config['amazon_s3']);
                }
            } else {
                $options = array();

                if (isset($config['aws_key'])) {
                    $options['key'] = $config['aws_key'];
                }

                if (isset($config['aws_secret_key'])) {
                    $options['secret'] = $config['aws_secret_key'];
                }

                $this->amazonS3 = new \AmazonS3($options);
            }
        } catch (\CFCredentials_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        if (isset($config['bucket'])) {
            $this->setBucket($config['bucket']);
        }

        if (isset($config['storage_class'])) {
            $this->setStorageClass($config['storage_class']);
        }

        if (isset($config['acl'])) {
            $this->setAcl($config['acl']);
        }

        if (isset($config['uri_expiration_time'])) {
            $this->setUriExpirationTime($config['uri_expiration_time']);
        }

        if (isset($config['mime_type_detector'])) {
            $this->setMimeTypeDetector($config['mime_type_detector']);
        }

        if (isset($config['filename_filter'])) {
            $this->setFilenameFilter($config['filename_filter']);
        }
    }

    /**
     * Set the internal AmazonS3 instance.
     *
     * @param \AmazonS3
     * @return AmazonS3
     */
    public function setAmazonS3(\AmazonS3 $amazonS3)
    {
        $this->amazonS3 = $amazonS3;

        return $this;
    }

    /**
     * Get the internal AmazonS3 instance.
     *
     * @return \AmazonS3
     */
    public function getAmazonS3()
    {
        return $this->amazonS3;
    }

    /**
     * Set the bucket.
     *
     * @param  string   $bucket
     * @return AmazonS3
     */
    public function setBucket($bucket)
    {
        $this->bucket = $bucket;

        return $this;
    }

    /**
     * Get the bucket.
     *
     * @return string
     */
    public function getBucket()
    {
        return $this->bucket;
    }

    /**
     * Set the storage class.
     *
     * @param  string   $storageClass
     * @return AmazonS3
     */
    public function setStorageClass($storageClass)
    {
        $this->storageClass = $storageClass;

        return $this;
    }

    /**
     * Get the storage class.
     *
     * @return string
     */
    public function getStorageClass()
    {
        if (null === $this->storageClass) {
            $this->setStorageClass(\AmazonS3::STORAGE_STANDARD);
        }

        return $this->storageClass;
    }

    /**
     * Set the acl.
     *
     * @param  string   $acl
     * @return AmazonS3
     */
    public function setAcl($acl)
    {
        $this->acl = $acl;

        return $this;
    }

    /**
     * Get the acl.
     *
     * @return string
     */
    public function getAcl()
    {
        if (null === $this->acl) {
            $this->setAcl(\AmazonS3::ACL_PRIVATE);
        }

        return $this->acl;
    }

    /**
     * Set the uri expiration time.
     *
     * @param  string|integer $uriExpirationTime
     * @return AmazonS3
     */
    public function setUriExpirationTime($uriExpirationTime)
    {
        $this->uriExpirationTime = $uriExpirationTime;

        return $this;
    }

    /**
     * Get the uri expiration time.
     *
     * @return string|integer
     */
    public function getUriExpirationTime()
    {
        return $this->uriExpirationTime;
    }

    /**
     * Set the mime type detector.
     *
     * @param  \DotsUnited\Cabinet\MimeType\Detector\DetectorInterface $mimeTypeDetetcor
     * @return Stream
     */
    public function setMimeTypeDetector(DetectorInterface $mimeTypeDetetcor)
    {
        $this->mimeTypeDetector = $mimeTypeDetetcor;

        return $this;
    }

    /**
     * Get the mime type detector.
     *
     * @return \DotsUnited\Cabinet\MimeType\Detector\DetectorInterface
     */
    public function getMimeTypeDetector()
    {
        if (null === $this->mimeTypeDetector) {
            $this->setMimeTypeDetector(new FileinfoDetector());
        }

        return $this->mimeTypeDetector;
    }

    /**
     * Set the filename filter.
     *
     * @param \DotsUnited\Cabinet\Filter\FilterInterface
     * @return Stream
     */
    public function setFilenameFilter(FilterInterface $filter)
    {
        $this->filenameFilter = $filter;

        return $this;
    }

    /**
     * Get the filename filter.
     *
     * @return \DotsUnited\Cabinet\Filter\FilterInterface
     */
    public function getFilenameFilter()
    {
        return $this->filenameFilter;
    }

    /**
     * Import a external local file.
     *
     * @param  string            $external The external local file
     * @param  string            $file     The name to store the file under
     * @return boolean
     * @throws \RuntimeException
     */
    public function import($external, $file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        $opt = array(
            'fileUpload' => (string) $external,
            'acl'        => $this->getAcl(),
            'storage'    => $this->getStorageClass()
        );

        $type = $this->getMimeTypeDetector()->detectFromFile($external);

        if (!empty($type)) {
            $opt['contentType'] = $type;
        }

        try {
            $response = $this->amazonS3->create_object($this->getBucket(), $file, $opt);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        return $response->isOk();
    }

    /**
     * Write data to a file.
     *
     * @param  string                $file
     * @param  string|array|resource $data
     * @return boolean
     * @throws \RuntimeException
     */
    public function write($file, $data)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        $opt = array(
            'acl'     => $this->getAcl(),
            'storage' => $this->getStorageClass()
        );

        if (is_resource($data)) {
            $opt['fileUpload'] = $data;
            $type = $this->getMimeTypeDetector()->detectFromResource($data);
        } else {
            if (is_array($data)) {
                $data = implode('', $data);
            }

            $opt['body'] = $data;
            $type = $this->getMimeTypeDetector()->detectFromString($data);
        }

        if (!empty($type)) {
            $opt['contentType'] = $type;
        }

        try {
            $response = $this->amazonS3->create_object($this->getBucket(), $file, $opt);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        return $response->isOk();
    }

    /**
     * Read data from a file.
     *
     * @param  string         $file
     * @return string|boolean The contents or false on failure
     */
    public function read($file)
    {
        $fp = $this->stream($file);

        if (!$fp) {
            return false;
        }

        $data = '';
        while (!feof($fp)) {
            $data .= fread($fp, 4096);
        }

        fclose($fp);

        return $data;
    }

    /**
     * Return a read-only stream resource for a file.
     *
     * @param  string            $file
     * @return resource|boolean  The resource or false on failure
     * @throws \RuntimeException
     */
    public function stream($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        $tmp = tmpfile();

        $opt = array(
            'fileDownload' => $tmp
        );

        try {
            $response = $this->amazonS3->get_object($this->getBucket(), $file, $opt);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        if (!$response->isOk()) {
            return false;
        }

        rewind($tmp);

        return $tmp;
    }

    /**
     * Copy a file internally.
     *
     * @param  string            $src
     * @param  string            $dest
     * @return boolean           Whether the file was copied
     * @throws \RuntimeException
     */
    public function copy($src, $dest)
    {
        if (null !== $this->filenameFilter) {
            $src  = $this->filenameFilter->filter($src);
            $dest = $this->filenameFilter->filter($dest);
        }

        $src = array(
            'bucket'   => $this->getBucket(),
            'filename' => $src
        );

        $dest = array(
            'bucket'   => $this->getBucket(),
            'filename' => $dest
        );

        $opt = array(
            'acl'     => $this->getAcl(),
            'storage' => $this->getStorageClass()
        );

        try {
            $response = $this->amazonS3->copy_object($src, $dest, $opt);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        return $response->isOk();
    }

    /**
     * Rename a file internally.
     *
     * @param  string  $src
     * @param  string  $dest
     * @return boolean Whether the file was renamed
     */
    public function rename($src, $dest)
    {
        if (!$this->copy($src, $dest)) {
            return false;
        }

        $this->unlink($src);

        return true;
    }

    /**
     * Delete a file.
     *
     * @param  string            $file
     * @return boolean           Whether the file was deleted
     * @throws \RuntimeException
     */
    public function unlink($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        try {
            $response = $this->amazonS3->delete_object($this->getBucket(), $file);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        return $response->isOk();
    }

    /**
     * Return whether a file exists.
     *
     * @param  string            $file
     * @return boolean           Whether the file exists
     * @throws \RuntimeException
     */
    public function exists($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        try {
            return $this->amazonS3->if_object_exists($this->getBucket(), $file) === true;
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }
    }

    /**
     * Return the files size.
     *
     * @param  string            $file
     * @return integer           The file size in bytes
     * @throws \RuntimeException
     */
    public function size($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        try {
            $response = $this->amazonS3->get_object_headers($this->getBucket(), $file);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        if (!$response->isOk()) {
            return false;
        }

        $header = array_change_key_case($response->header, CASE_LOWER);

        if (!isset($header['content-length'])) {
            return false;
        }

        return (integer) $header['content-length'];
    }

    /**
     * Try to determine and return a files MIME content type.
     *
     * @param  string            $file
     * @return string            The MIME content type
     * @throws \RuntimeException
     */
    public function type($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        try {
            $response = $this->amazonS3->get_object_headers($this->getBucket(), $file);
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }

        if (!$response->isOk()) {
            return null;
        }

        $header = array_change_key_case($response->header, CASE_LOWER);

        if (!isset($header['content-type'])) {
            return null;
        }

        return $header['content-type'];
    }

    /**
     * Return the web-accessible uri for the given file.
     *
     * @param  string            $file
     * @return string            The file uri
     * @throws \RuntimeException
     */
    public function uri($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        try {
            return $this->amazonS3->get_object_url($this->getBucket(), $file, $this->getUriExpirationTime());
        } catch (\S3_Exception $e) {
            throw new \RuntimeException('Exception thrown by \AmazonS3: ' . $e->getMessage(), null, $e);
        }
    }
}
