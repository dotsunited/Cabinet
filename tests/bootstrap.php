<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2011 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../vendor/sdk/sdk.class.php';
$loader = require __DIR__.'/../vendor/.composer/autoload.php';
$loader->add('DotsUnited\\Cabinet', __DIR__);
