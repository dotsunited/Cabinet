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
 * DotsUnited\Cabinet\MimeType\Detector\Fileinfo
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
class Fileinfo implements DetectorInterface
{
    /**
     * The magic file.
     *
     * @var string
     */
    protected $magicFile;

    /**
     * The finfo handle.
     *
     * @var resource
     */
    protected $handle;

    /**
     * Contructor.
     *
     * @param string $magicFile
     */
    public function __construct($magicFile = null)
    {
        $this->magicFile = $magicFile;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        if (null !== $this->handle) {
            finfo_close($this->handle);
        }
    }

    /**
     * Detect mime type from a file.
     *
     * @param string $file
     * @return string
     */
    public function detectFromFile($file)
    {
        $type = finfo_file($this->getHandle(), $file);
        return $this->fixType($type);
    }

    /**
     * Detect mime type from a string.
     *
     * @param string $string
     * @return string
     */
    public function detectFromString($string)
    {
        $type = finfo_buffer($this->getHandle(), $string);
        return $this->fixType($type);
    }

    /**
     * Detect mime type from a resource.
     *
     * @param resource $resource
     * @return string
     */
    public function detectFromResource($resource)
    {
        $handle = $this->getHandle();
        $meta   = stream_get_meta_data($resource);

        if (file_exists($meta['uri'])) {
            $type = finfo_file($handle, $meta['uri']);
        } else {
            $type = finfo_buffer($handle, fread($resource, 1000000));
        }

        return $this->fixType($type);
    }

    /**
     * Get finfo handle.
     *
     * @return resource
     */
    protected function getHandle()
    {
        if (null === $this->handle) {
            if ($this->magicFile) {
                $this->handle = finfo_open(FILEINFO_MIME, $this->magicFile);
            } else {
                $this->handle = finfo_open(FILEINFO_MIME);
            }
        }

        return $this->handle;
    }

    /**
     * Fix mime type returned by finfo.
     *
     * @param string $type
     * @return string|null
     */
    protected function fixType($type)
    {
        if (false !== ($pos = strpos($type, ';'))) {
            $type = substr($type, 0, $pos);
        }

        if (empty($type) || $type == 'application/x-empty') {
            return null;
        }

        return $type;
    }
}
