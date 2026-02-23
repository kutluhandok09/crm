<?php

namespace Tests\Unit\Security;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NoDangerousRawSqlUsageTest extends TestCase
{
    public function test_application_code_does_not_use_dangerous_raw_sql_entrypoints(): void
    {
        $patterns = [
            'whereRaw(',
            'orWhereRaw(',
            'havingRaw(',
            'orderByRaw(',
            'groupByRaw(',
            'selectRaw(',
            'DB::raw(',
            'DB::select(',
            'DB::statement(',
            'DB::unprepared(',
        ];

        $files = collect(File::allFiles(app_path()))
            ->merge(File::allFiles(base_path('routes')))
            ->filter(fn ($file) => $file->getExtension() === 'php');

        $hits = [];

        foreach ($files as $file) {
            $content = File::get($file->getPathname());
            foreach ($patterns as $pattern) {
                if (str_contains($content, $pattern)) {
                    $hits[] = $file->getRelativePathname()." contains {$pattern}";
                }
            }
        }

        $this->assertEmpty(
            $hits,
            "Dangerous raw SQL entrypoints detected:\n".implode("\n", $hits)
        );
    }
}
