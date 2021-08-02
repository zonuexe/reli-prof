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

namespace PhpProfiler\Lib\Process\MemoryMap;

use PhpProfiler\Lib\String\LineFetcher;

/**
 * Class ProcessMemoryMapCreator
 * @package PhpProfiler\ProcessReader
 */
final class ProcessMemoryMapCreator
{
    /**
     * @return static
     */
    public static function create(): self
    {
        return new self(
            new ProcessMemoryMapReader(),
            new ProcessMemoryMapParser(new LineFetcher())
        );
    }

    /**
     * ProcessMemoryMapCreator constructor.
     */
    public function __construct(
        private ProcessMemoryMapReader $memory_map_reader,
        private ProcessMemoryMapParser $memory_map_parser
    ) {
    }

    /**
     * @param int $pid
     * @return ProcessMemoryMap
     */
    public function getProcessMemoryMap(int $pid): ProcessMemoryMap
    {
        $memory_map_raw = $this->memory_map_reader->read($pid);
        return $this->memory_map_parser->parse($memory_map_raw);
    }
}
