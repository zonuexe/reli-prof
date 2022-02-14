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

namespace PhpProfiler\Lib\PhpInternals;

use FFI\CData;

/** @template T of CData */
final class CastedCData
{
    /**
     * @param CData $raw
     * @param T $casted
     */
    public function __construct(
        public object $raw,
        public object $casted,
    ) {
    }
}