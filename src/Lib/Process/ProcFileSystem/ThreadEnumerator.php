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

namespace PhpProfiler\Lib\Process\ProcFileSystem;

final class ThreadEnumerator
{
    /** @return \Generator<int> */
    public function getThreadIds(int $pid)
    {
        /**
         * @var string $full_path
         * @var \SplFileInfo $item
         */
        foreach (new \DirectoryIterator("/proc/{$pid}/task/") as $item) {
            if (file_exists($item->getPathname()) === false) {
                continue;
            }
            if (!is_numeric(basename($item->getPathname()))) {
                continue;
            }
            yield (int)basename($item->getPathname());
        }
    }
}
