<?php

/**
 * This file is part of the reliforp/reli-prof package.
 *
 * (c) sji <sji@sj-i.dev>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Reli\Lib\PhpInternals\Constants;

final class PhpInternalsConstantsV80 extends VersionAwareConstants
{
    public const ZEND_ACC_CLOSURE = (1 << 22);
    public const ZEND_ACC_HAS_RETURN_TYPE = (1 << 13);
}
