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

namespace PhpProfiler\Inspector\Settings;

use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;

class TargetProcessSettingsTest extends TestCase
{
    public function testFromConsoleInput(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->expects()->getOption('pid')->andReturns(10);

        $settings = TargetProcessSettings::fromConsoleInput($input);

        $this->assertSame(10, $settings->pid);
    }

    public function testFromConsoleInputPidNotSpecified(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->expects()->getOption('pid')->andReturns(null);
        $this->expectException(TargetProcessInspectorSettingsException::class);
        $settings = TargetProcessSettings::fromConsoleInput($input);
    }

    public function testFromConsoleInputPidNotInterger(): void
    {
        $input = Mockery::mock(InputInterface::class);
        $input->expects()->getOption('pid')->andReturns('abc');
        $this->expectException(TargetProcessInspectorSettingsException::class);
        $settings = TargetProcessSettings::fromConsoleInput($input);
    }
}
