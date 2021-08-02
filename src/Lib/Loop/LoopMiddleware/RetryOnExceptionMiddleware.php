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

namespace PhpProfiler\Lib\Loop\LoopMiddleware;

use Exception;
use PhpProfiler\Lib\Loop\LoopMiddlewareInterface;

final class RetryOnExceptionMiddleware implements LoopMiddlewareInterface
{
    private int $current_retry_count = 0;

    /**
     * RetryOnExceptionLoop constructor.
     * @param int $max_retry
     * @param array<int, class-string<Exception>> $exception_names
     * @param LoopMiddlewareInterface $chain
     */
    public function __construct(
        private int $max_retry,
        private array $exception_names,
        private LoopMiddlewareInterface $chain,
    ) {
    }

    public function invoke(): bool
    {
        while ($this->current_retry_count <= $this->max_retry or $this->max_retry === -1) {
            try {
                $result = $this->chain->invoke();
                $this->current_retry_count = 0;
                return $result;
            } catch (Exception $e) {
                if (in_array(get_class($e), $this->exception_names, true)) {
                    $this->current_retry_count++;
                    continue;
                }
                throw $e;
            }
        }
        return false;
    }
}
