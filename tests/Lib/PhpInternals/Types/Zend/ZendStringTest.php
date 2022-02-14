<?php

/**
 * This file is part of the sj-i/php-profiler package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhpProfiler\Lib\PhpInternals\Types\Zend;

use PhpProfiler\Lib\PhpInternals\CastedCData;
use PhpProfiler\Lib\PhpInternals\Types\C\RawString;
use PhpProfiler\Lib\Process\Pointer\Pointer;
use PHPUnit\Framework\TestCase;

class ZendStringTest extends TestCase
{
    public function testValues(): void
    {
        $string_addr = \FFI::addr($buf = \FFI::new('char[16]'));
        $zend_string = new ZendString(
            new CastedCData(
                new class () {
                },
                (object)[
                    'h' => 123,
                    'len' => 456,
                    'val' => $string_addr,
                ],
            ),
            24
        );
        $this->assertSame(123, $zend_string->h);
        $this->assertSame(456, $zend_string->len);
        $this->assertSame(
            \FFI::cast('long', $string_addr)->cdata,
            $zend_string->val->address
        );
    }

    public function testGetValuePointer(): void
    {
        $string_addr = \FFI::addr($buf = \FFI::new('char[16]'));
        $zend_string = new ZendString(
            new CastedCData(
                new class () {
                },
                (object)[
                    'h' => 123,
                    'len' => 456,
                    'val' => $string_addr,
                ],
            ),
            24
        );
        $value_pointer = $zend_string->getValuePointer(
            new Pointer(RawString::class, 123, 16)
        );
        $this->assertSame(RawString::class, $value_pointer->type);
        $this->assertSame(147, $value_pointer->address);
        $this->assertSame(456, $value_pointer->size);
    }
}