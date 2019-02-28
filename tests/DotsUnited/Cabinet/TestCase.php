<?php

/*
 * This file is part of Cabinet.
 *
 * (c) 2013 Jan Sorgalla <jan.sorgalla@dotsunited.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DotsUnited\Cabinet;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        if (!\method_exists($this, 'expectException')) {
            parent::setExpectedException(
                $exceptionName,
                $exceptionMessage,
                $exceptionCode
            );
            return;
        }

        $this->expectException($exceptionName);

        if ('' !== $exceptionMessage) {
            $this->expectExceptionMessage($exceptionMessage);
        }

        if (null !== $exceptionCode) {
            $this->expectExceptionCode($exceptionCode);
        }
    }
}
