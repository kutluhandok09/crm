<?php

namespace Tests\Unit\Security;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BladeEscapingPolicyTest extends TestCase
{
    public function test_unescaped_blade_output_is_restricted_to_whitelisted_templates(): void
    {
        $allowed = [
            'profile/partials/two-factor-authentication-form.blade.php',
        ];

        $bladeFiles = collect(File::allFiles(resource_path('views')))
            ->filter(fn ($file) => $file->getExtension() === 'php');

        $hits = [];

        foreach ($bladeFiles as $file) {
            $content = File::get($file->getPathname());
            if (! str_contains($content, '{!!')) {
                continue;
            }

            $relative = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $file->getPathname());
            $normalizedRelative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);

            if (! in_array($normalizedRelative, $allowed, true)) {
                $hits[] = $normalizedRelative;
            }
        }

        $this->assertEmpty(
            $hits,
            'Unescaped Blade output found in non-whitelisted templates: '.implode(', ', $hits)
        );
    }
}
