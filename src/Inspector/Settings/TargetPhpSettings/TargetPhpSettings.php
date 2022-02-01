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

namespace PhpProfiler\Inspector\Settings\TargetPhpSettings;

use PhpProfiler\Lib\PhpInternals\ZendTypeReader;

/**
 * @template TVersion of value-of<ZendTypeReader::ALL_SUPPORTED_VERSIONS>|'auto'
 * @immutable
 */
final class TargetPhpSettings
{
    public const PHP_REGEX_DEFAULT = '.*/(php(74|7.4|80|8.0)?|php-fpm|libphp[78]?.*\.so)$';
    public const LIBPTHREAD_REGEX_DEFAULT = '.*/libpthread.*\.so';
    public const TARGET_PHP_VERSION_DEFAULT = 'auto';

    /** @param TVersion $php_version */
    public function __construct(
        public string $php_regex = self::PHP_REGEX_DEFAULT,
        public string $libpthread_regex = self::LIBPTHREAD_REGEX_DEFAULT,
        public string $php_version = self::TARGET_PHP_VERSION_DEFAULT,
        public ?string $php_path = null,
        public ?string $libpthread_path = null
    ) {
        $this->php_regex = '{' . $php_regex . '}';
        $this->libpthread_regex = '{' . $libpthread_regex . '}';
    }
}
