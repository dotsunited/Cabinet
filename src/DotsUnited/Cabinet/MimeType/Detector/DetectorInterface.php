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
 * DotsUnited\Cabinet\MimeType\Detector\DetectorInterface
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
interface DetectorInterface
{
    /**
     * Detect mime type from a file.
     *
     * @param  string $file
     * @return string
     */
    public function detectFromFile($file);

    /**
     * Detect mime type from a string.
     *
     * @param  string $string
     * @return string
     */
    public function detectFromString($string);

    /**
     * Detect mime type from a resource.
     *
     * @param  resource $resource
     * @return string
     */
    public function detectFromResource($resource);
}
