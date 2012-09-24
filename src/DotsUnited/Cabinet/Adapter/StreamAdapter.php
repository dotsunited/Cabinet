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
 * DotsUnited\Cabinet\Adapter\StreamAdapter
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 */
class StreamAdapter implements AdapterInterface
{
    /**
     * The base path.
     *
     * @var string
     */
    private $basePath;

    /**
     * The base uri.
     *
     * @var string
     */
    private $baseUri;

    /**
     * The directory umask.
     *
     * @var integer
     */
    private $directoryUmask = 0700;

    /**
     * The file umask.
     *
     * @var integer
     */
    private $fileUmask = 0600;

    /**
     * The stream context.
     *
     * @var array|resource
     */
    private $streamContext;

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
     * @param array $config
     */
    public function __construct(array $config = array())
    {
        if (isset($config['base_path'])) {
            $this->setBasePath($config['base_path']);
        }

        if (isset($config['base_uri'])) {
            $this->setBaseUri($config['base_uri']);
        }

        if (isset($config['directory_umask'])) {
            $this->setDirectoryUmask($config['directory_umask']);
        }

        if (isset($config['file_umask'])) {
            $this->setFileUmask($config['file_umask']);
        }

        if (isset($config['stream_context'])) {
            $this->setStreamContext($config['stream_context']);
        }

        if (isset($config['mime_type_detector'])) {
            $this->setMimeTypeDetector($config['mime_type_detector']);
        }

        if (isset($config['filename_filter'])) {
            $this->setFilenameFilter($config['filename_filter']);
        }
    }

    /**
     * Set the base path.
     *
     * @param  string $basePath
     * @return Stream
     */
    public function setBasePath($basePath)
    {
        if (null !== $basePath) {
            if (substr($basePath, -3) != '://') {
                $basePath = rtrim($basePath, '/\\');
            }
        }

        $this->basePath = $basePath;

        return $this;
    }

    /**
     * Get the base path.
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * Set the base uri.
     *
     * @param  string $baseUri
     * @return Stream
     */
    public function setBaseUri($baseUri)
    {
        if (null !== $baseUri) {
            if (substr($baseUri, -3) != '://') {
                $baseUri = rtrim($baseUri, '/\\');
            }
        }

        $this->baseUri = $baseUri;

        return $this;
    }

    /**
     * Get the base uri.
     *
     * @return string
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * Set the directory umask.
     *
     * @param  integer|string $directoryUmask
     * @return Stream
     */
    public function setDirectoryUmask($directoryUmask)
    {
        if (null !== $directoryUmask) {
            if (is_string($directoryUmask)) {
                $directoryUmask = octdec($directoryUmask);
            }
        }

        $this->directoryUmask = $directoryUmask;

        return $this;
    }

    /**
     * Get the directory umask.
     *
     * @return integer
     */
    public function getDirectoryUmask()
    {
        return $this->directoryUmask;
    }

    /**
     * Set the file umask.
     *
     * @param  integer|string $fileUmask
     * @return Stream
     */
    public function setFileUmask($fileUmask)
    {
        if (null !== $fileUmask) {
            if (is_string($fileUmask)) {
                $fileUmask = octdec($fileUmask);
            }
        }

        $this->fileUmask = $fileUmask;

        return $this;
    }

    /**
     * Get the file umask.
     *
     * @return integer
     */
    public function getFileUmask()
    {
        return $this->fileUmask;
    }

    /**
     * Set the stream context.
     *
     * @param  array|resource $streamContext
     * @return Stream
     */
    public function setStreamContext($streamContext)
    {
        $this->streamContext = $streamContext;

        return $this;
    }

    /**
     * Get the stream context.
     *
     * @return resource
     */
    public function getStreamContext()
    {
        if (null === $this->streamContext) {
            $this->streamContext = stream_context_create();
        } elseif (is_array($this->streamContext)) {
            $this->streamContext = stream_context_create($this->streamContext);
        }

        return $this->streamContext;
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
     * @param  string  $external The external local file
     * @param  string  $file     The name to store the file under
     * @return boolean
     */
    public function import($external, $file)
    {
        $external = (string) $external;

        $path = $this->makePath($file);

        $ret = copy($external, $path, $this->getStreamContext());

        if ($ret) {
            @chmod($path, $this->getFileUmask());
        }

        return $ret;
    }

    /**
     * Write data to a file.
     *
     * @param  string                $file
     * @param  string|array|resource $data
     * @return boolean
     */
    public function write($file, $data)
    {
        $path = $this->makePath($file);

        $ret = (boolean) file_put_contents($path, $data, null, $this->getStreamContext());

        if ($ret) {
            @chmod($path, $this->getFileUmask());
        }

        return $ret;
    }

    /**
     * Read data from a file.
     *
     * @param  string         $file
     * @return string|boolean The contents or false on failure
     */
    public function read($file)
    {
        $path = $this->path($file);

        return file_get_contents($path, null, $this->getStreamContext());
    }

    /**
     * Return a read-only stream resource for a file.
     *
     * @param  string           $file
     * @return resource|boolean The resource or false on failure
     */
    public function stream($file)
    {
        $path = $this->path($file);

        return fopen($path, 'rb', false, $this->getStreamContext());
    }

    /**
     * Copy a file internally.
     *
     * @param  string  $src
     * @param  string  $dest
     * @return boolean Whether the file was copied
     */
    public function copy($src, $dest)
    {
        $srcPath  = $this->path($src);
        $destPath = $this->makePath($dest);

        $ret = copy($srcPath, $destPath, $this->getStreamContext());

        if ($ret) {
            @chmod($destPath, $this->getFileUmask());
        }

        return $ret;
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
        $srcPath  = $this->path($src);
        $destPath = $this->makePath($dest);

        $ret = rename($srcPath, $destPath, $this->getStreamContext());

        if ($ret) {
            @chmod($destPath, $this->getFileUmask());
            $this->deletePath($src);
        } else {
            $this->deletePath($dest);
        }

        return $ret;
    }

    /**
     * Delete a file.
     *
     * @param  string  $file
     * @return boolean Whether the file was deleted
     */
    public function unlink($file)
    {
        $path = $this->path($file);

        $ret = unlink($path, $this->getStreamContext());

        $this->deletePath($file);

        return $ret;
    }

    /**
     * Return whether a file exists.
     *
     * @param  string  $file
     * @return boolean Whether the file exists
     */
    public function exists($file)
    {
        $path = $this->path($file);

        return file_exists($path);
    }

    /**
     * Return the files size.
     *
     * @param  string  $file
     * @return integer The file size in bytes
     */
    public function size($file)
    {
        $path = $this->path($file);

        return filesize($path);
    }

    /**
     * Try to determine and return a files MIME content type.
     *
     * @param  string $file
     * @return string The MIME content type
     */
    public function type($file)
    {
        $path = $this->path($file);

        return $this->getMimeTypeDetector()->detectFromFile($path);
    }

    /**
     * Return the web-accessible uri for the given file.
     *
     * @param  string $file
     * @return string The file uri
     */
    public function uri($file)
    {
        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        return $this->getBaseUri() . '/' .  str_replace(array('\\', '/'), '/', $file);
    }

    /**
     * Return the path for the given file.
     *
     * @param  string $file
     * @return string The file path
     */
    public function path($file)
    {
        $sep  = DIRECTORY_SEPARATOR;
        $path = $this->getBasePath();

        if (null !== $path) {
            if (strpos($path, '://') !== false) {
                $sep = '/';
            }

            $path .= $sep;
        }

        if (null !== $this->filenameFilter) {
            $file = $this->filenameFilter->filter($file);
        }

        return $path . str_replace(array('\\', '/'), $sep, $file);
    }

    /**
     * Return a file's parent.
     *
     * <code>null</code> is returned if the file has no parent.
     *
     * @param  string $file
     * @return string The parent directory of a file
     */
    private function parent($file)
    {
        $parent = dirname(rtrim($file, '/\\'));

        if (!empty($parent) && $parent != '.') {
            return $parent;
        }

        return null;
    }

    /**
     * Create a file path for the given file.
     *
     * @param  string         $file
     * @return string|boolean The path on success, false otherwise
     */
    private function makePath($file)
    {
        $path     = $this->path($file);
        $dir      = $this->parent($path);
        $basePath = $this->getBasePath();

        if (null !== $dir && $dir !== $basePath) {
            $dir  = substr($dir, strlen($basePath));

            $parts = preg_split('/[\\\\\/]+/', trim($dir, '/\\'));

            $dirUmask      = $this->getDirectoryUmask();
            $dirPath       = $basePath;
            $streamContext = $this->getStreamContext();

            foreach ($parts as $part) {
                $dirPath .= DIRECTORY_SEPARATOR . $part;
                if (!is_dir($dirPath)) {
                    mkdir($dirPath, $dirUmask, false, $streamContext);
                    @chmod($dirPath, $dirUmask); // Required in some configurations
                }
            }
        }

        return $path;
    }

    /**
     * Delete the internal path for a file.
     *
     * @param  string $file
     * @return void
     */
    private function deletePath($file)
    {
        $path          = $this->path($file);
        $parent        = $this->parent($path);
        $basePath      = $this->getBasePath();
        $streamContext = $this->getStreamContext();

        while (true) {
            if (null === $parent || $parent === $basePath || !file_exists($parent)) {
                break;
            }

            if (count(scandir($parent, null, $streamContext)) > 2) { // Folder contains files
                break;
            }

            if (!rmdir($parent, $streamContext)) { // Something went wrong
                break;
            }

            $parent = $this->parent($parent);
        }
    }
}
