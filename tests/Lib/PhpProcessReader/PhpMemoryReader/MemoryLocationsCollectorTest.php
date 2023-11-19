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

namespace Reli\Lib\PhpProcessReader\PhpMemoryReader;

use Reli\BaseTestCase;
use Reli\Inspector\Settings\TargetPhpSettings\TargetPhpSettings;
use Reli\Lib\ByteStream\IntegerByteSequence\LittleEndianReader;
use Reli\Lib\Elf\Parser\Elf64Parser;
use Reli\Lib\Elf\Process\PerBinarySymbolCacheRetriever;
use Reli\Lib\Elf\Process\ProcessModuleSymbolReaderCreator;
use Reli\Lib\Elf\SymbolResolver\Elf64SymbolResolverCreator;
use Reli\Lib\File\CatFileReader;
use Reli\Lib\PhpInternals\ZendTypeReader;
use Reli\Lib\PhpInternals\ZendTypeReaderCreator;
use Reli\Lib\PhpProcessReader\PhpGlobalsFinder;
use Reli\Lib\PhpProcessReader\PhpMemoryReader\ContextAnalyzer\ContextAnalyzer;
use Reli\Lib\PhpProcessReader\PhpMemoryReader\LocationTypeAnalyzer\LocationTypeAnalyzer;
use Reli\Lib\PhpProcessReader\PhpMemoryReader\ObjectClassAnalyzer\ObjectClassAnalyzer;
use Reli\Lib\PhpProcessReader\PhpMemoryReader\RegionAnalyzer\RegionAnalyzer;
use Reli\Lib\PhpProcessReader\PhpSymbolReaderCreator;
use Reli\Lib\PhpProcessReader\PhpZendMemoryManagerChunkFinder;
use Reli\Lib\Process\MemoryMap\ProcessMemoryMapCreator;
use Reli\Lib\Process\MemoryReader\MemoryReader;
use Reli\Lib\Process\ProcessSpecifier;

class MemoryLocationsCollectorTest extends BaseTestCase
{
    /** @var resource|null */
    private $child = null;

    private string $memory_limit_buckup;
    public function setUp(): void
    {
        $this->memory_limit_buckup = ini_get('memory_limit');
        ini_set('memory_limit', '1G');
    }

    protected function tearDown(): void
    {
        ini_set('memory_limit', $this->memory_limit_buckup);
        if (!is_null($this->child)) {
            $child_status = proc_get_status($this->child);
            if (is_array($child_status)) {
                if ($child_status['running']) {
                    posix_kill($child_status['pid'], SIGKILL);
                }
            }
        }
    }

    public function testCollectAll()
    {
        $memory_reader = new MemoryReader();
        $type_reader_creator = new ZendTypeReaderCreator();

        $this->child = proc_open(
            [
                PHP_BINARY,
                '-r',
                <<<CODE
                class A {
                    public static \$output = STDOUT;
                    public string \$result = '';
                    public function wait(\$input): void {
                        \$this->result = fgets(\$input);
                    }
                }
                \$object = new A;
                fputs(A::\$output, "a\n");
                \$object->wait(STDIN);
                CODE
            ],
            [
                ['pipe', 'r'],
                ['pipe', 'w'],
                ['pipe', 'w']
            ],
            $pipes
        );

        fgets($pipes[1]);
        $child_status = proc_get_status($this->child);
        $php_symbol_reader_creator = new PhpSymbolReaderCreator(
            $memory_reader,
            new ProcessModuleSymbolReaderCreator(
                new Elf64SymbolResolverCreator(
                    new CatFileReader(),
                    new Elf64Parser(
                        new LittleEndianReader()
                    )
                ),
                $memory_reader,
                new PerBinarySymbolCacheRetriever(),
            ),
            ProcessMemoryMapCreator::create(),
            new LittleEndianReader()
        );
        $php_globals_finder = new PhpGlobalsFinder(
            $php_symbol_reader_creator,
            new LittleEndianReader(),
            new MemoryReader()
        );

        /** @var int $child_status['pid'] */
        $executor_globals_address = $php_globals_finder->findExecutorGlobals(
            new ProcessSpecifier($child_status['pid']),
            new TargetPhpSettings()
        );
        $compiler_globals_address = $php_globals_finder->findCompilerGlobals(
            new ProcessSpecifier($child_status['pid']),
            new TargetPhpSettings()
        );

        $memory_locations_collector = new MemoryLocationsCollector(
            $memory_reader,
            $type_reader_creator,
            new PhpZendMemoryManagerChunkFinder(
                ProcessMemoryMapCreator::create(),
                $type_reader_creator,
                $php_globals_finder
            )
        );
        $collected_memories = $memory_locations_collector->collectAll(
            new ProcessSpecifier($child_status['pid']),
            new TargetPhpSettings(php_version: ZendTypeReader::V81),
            $executor_globals_address,
            $compiler_globals_address
        );
        $this->assertGreaterThan(0, $collected_memories->memory_get_usage_size);
        $this->assertGreaterThan(0, $collected_memories->memory_get_usage_real_size);

        $region_analyzer = new RegionAnalyzer(
            $collected_memories->chunk_memory_locations,
            $collected_memories->huge_memory_locations,
            $collected_memories->vm_stack_memory_locations,
            $collected_memories->compiler_arena_memory_locations
        );
        $region_analized = $region_analyzer->analyze($collected_memories->memory_locations);
        $this->assertGreaterThan(0, $region_analized->summary->zend_mm_heap_usage);
        $this->assertLessThanOrEqual(
            $collected_memories->memory_get_usage_size,
            $region_analized->summary->zend_mm_heap_usage
        );
        $this->assertSame(
            $collected_memories->memory_get_usage_real_size,
            $region_analized->summary->zend_mm_heap_total
        );
        $location_type_analyzer = new LocationTypeAnalyzer();
        $location_type_analized_result = $location_type_analyzer->analyze(
            $region_analized->regional_memory_locations->locations_in_zend_mm_heap,
        );
        $this->assertSame(
            1,
            $location_type_analized_result->per_type_usage['ZendObjectMemoryLocation']['count']
        );
        $object_class_analyzer = new ObjectClassAnalyzer();
        $object_class_analyzer_result = $object_class_analyzer->analyze(
            $region_analized->regional_memory_locations->locations_in_zend_mm_heap,
        );
        $this->assertSame(1, $object_class_analyzer_result->per_class_usage['A']['count']);
        $context_analyzer = new ContextAnalyzer();
        $contexts_analyzed = $context_analyzer->analyze(
            $collected_memories->top_reference_context
        );
        $this->assertSame(
            'fgets',
            $contexts_analyzed['call_frames']['0']['#function_name']
        );
        $this->assertSame(
            'ResourceContext',
            $contexts_analyzed['call_frames']['0']['local_variables']['$args_to_internal_function[0]']['#type']
        );
    }
}
