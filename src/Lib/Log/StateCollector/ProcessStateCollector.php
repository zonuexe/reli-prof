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

namespace PhpProfiler\Lib\Log\StateCollector;

use function getmypid;
use function memory_get_peak_usage;
use function memory_get_usage;

final class ProcessStateCollector implements StateCollector
{
    public function collect(): array
    {
        return [
            'pid' => getmypid(),
            'memory_usage' => memory_get_usage(),
            'peak_memory_usage' => memory_get_peak_usage(),
        ];
    }
}
