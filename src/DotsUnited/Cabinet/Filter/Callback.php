<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet\Filter;

/**
 * DotsUnited\Cabinet\Filter\Callback
 *
 * @author  Jan Sorgalla <jan.sorgalla@dotsunited.de>
 * @version @package_version@
 */
class Callback implements FilterInterface
{
    /**
     * @var mixed
     */
    protected $callback;

    /**
     * Constructor.
     * 
     * @param mixed $callback
     */
    public function __construct($callback)
    {
        $this->callback = $callback;
    }

    /**
     * Returns the result of filtering $value.
     *
     * @param mixed $value
     * @return mixed
     */
    public function filter($value)
    {
        return call_user_func($this->callback, $value);
    }
}
