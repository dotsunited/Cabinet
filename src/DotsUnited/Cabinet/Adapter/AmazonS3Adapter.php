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
use Aws\S3\Exception\S3Exception;

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
     * Aws\S3\S3Client class instance.
     *
     * @var Aws\S3\S3Client
     */
    private $s3Client;

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
     *   Aws\S3\Enum\StorageClass::STANDARD
     *   Aws\S3\Enum\StorageClass::REDUCED_REDUNDANCY
     *
     * The default value is Aws\S3\Enum\StorageClass::STANDARD.
     *
     * @var string
     */
    private $storageClass;

    /**
     * The ACL settings for files.
     *
     * Allowed values:
     *   Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS
     *   Aws\S3\Enum\CannedAcl::PUBLIC_READ
     *   Aws\S3\Enum\CannedAcl::PUBLIC_READ_WRITE
     *   Aws\S3\Enum\CannedAcl::AUTHENTICATED_READ
     *   Aws\S3\Enum\CannedAcl::BUCKET_OWNER_READ
     *   Aws\S3\Enum\CannedAcl::BUCKET_OWNER_FULL_CONTROL
     *
     * The default value is Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS.
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
     * Whether to throw exceptions.
     *
     * @var booleam
     */
    private $throwExceptions = true;

    /**
     * Constructor.
     *
     * @param  array             $config
     * @throws \RuntimeException
     */
    public function __construct(array $config = array())
    {
        if (isset($config['s3_client'])) {
            $this->setS3Client($config['s3_client']);
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

        if (isset($config['throw_exceptions'])) {
            $this->setThrowExceptions($config['throw_exceptions']);
        }
    }

    /**
     * Set the internal Aws\S3\S3Client instance.
     *
     * @param Aws\S3\S3Client
     * @return AmazonS3
     */
    public function setS3Client(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;

        return $this;
    }

    /**
     * Get the internal Aws\S3\S3Client instance.
     *
     * @return Aws\S3\S3Client
     */
    public function getS3Client()
    {
        return $this->s3Client;
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
            $this->setStorageClass(\Aws\S3\Enum\StorageClass::STANDARD);
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
            $this->setAcl(\Aws\S3\Enum\CannedAcl::PRIVATE_ACCESS);
        }

        return $this->acl;
    }

    /**
     * Set the uri expiration time.
     *
     * Can bei either a Unix timestamp or a string that can be evaluated
     * by strtotime.
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
     * Set whether to throw exceptions.
     *
     * @param boolean
     * @return AmazonS3
     */
    public function setThrowExceptions($throwExceptions)
    {
        $this->throwExceptions = $throwExceptions;

        return $this;
    }

    /**
     * Get whether to throw exceptions.
     *
     * @return boolean
     */
    public function getThrowExceptions()
    {
        return $this->throwExceptions;
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

        if (!is_resource($external)) {
            $external = fopen($external, 'r');
        }

        $params = array(
            'Bucket'       => $this->getBucket(),
            'Key'          => $file,
            'Body'         => $external,
            'ACL'          => $this->getAcl(),
            'StorageClass' => $this->getStorageClass()
        );

        $type = $this->getMimeTypeDetector()->detectFromFile($file);

        if (!empty($type)) {
            $params['ContentType'] = $type;
        }

        try {
            $this->s3Client->putObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return true;
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

        if (is_resource($data)) {
            $type = $this->getMimeTypeDetector()->detectFromResource($data);
        } else {
            if (is_array($data)) {
                $data = implode('', $data);
            }

            $type = $this->getMimeTypeDetector()->detectFromString($data);
        }

        $params = array(
            'Bucket'       => $this->getBucket(),
            'Key'          => $file,
            'Body'         => $data,
            'ACL'          => $this->getAcl(),
            'StorageClass' => $this->getStorageClass()
        );

        if (!empty($type)) {
            $params['ContentType'] = $type;
        }

        try {
            $this->s3Client->putObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return true;
    }

    /**
     * Read data from a file.
     *
     * @param  string         $file
     * @return string|boolean The contents or false on failure
     */
    public function read($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        $params = array(
            'Bucket' => $this->getBucket(),
            'Key'    => $file
        );

        try {
            $response = $this->s3Client->getObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return $response->get('Body')->__toString();
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

        $params = array(
            'Bucket' => $this->getBucket(),
            'Key'    => $file
        );

        try {
            $response = $this->s3Client->getObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return $response->get('Body')->getStream();
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

        $params = array(
            'Bucket'       => $this->getBucket(),
            'Key'          => $dest,
            'ACL'          => $this->getAcl(),
            'StorageClass' => $this->getStorageClass(),
            'CopySource'   => urlencode($this->getBucket() . '/' . $src)
        );

        try {
            $this->s3Client->copyObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return true;
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

        $params = array(
            'Bucket' => $this->getBucket(),
            'Key'    => $file
        );

        try {
            $this->s3Client->deleteObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return true;
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
            return $this->s3Client->doesObjectExist($this->getBucket(), $file);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
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

        $params = array(
            'Bucket' => $this->getBucket(),
            'Key'    => $file
        );

        try {
            $response = $this->s3Client->headObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return false;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        $size = $response->get('ContentLength');

        if (null === $size) {
            return false;
        }

        return (integer) $size;
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

        $params = array(
            'Bucket' => $this->getBucket(),
            'Key'    => $file
        );

        try {
            $response = $this->s3Client->headObject($params);
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return null;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }

        return $response->get('ContentType');
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
            $request = $this->s3Client->get($this->getBucket() . '/' . $file);
            return $this->s3Client->getPresignedUrl($request, $this->getUriExpirationTime());
        } catch (S3Exception $e) {
            if (!$this->getThrowExceptions()) {
                return null;
            }

            throw new \RuntimeException('Exception thrown by Aws\S3\S3Client: ' . $e->getMessage(), null, $e);
        }
    }
}
