<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Zarbinco\LaravelVandar\Tests\TestCase;

final class DocumentationConsistencyTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private array $docFiles = [
        'usage.md',
        'security.md',
        'testing.md',
        'endpoint-support.md',
        'laravel-payment-integration.md',
        'production-checklist.md',
        'release-checklist.md',
        'roadmap.md',
    ];

    public function test_root_readme_is_short_english_landing_page_with_language_links(): void
    {
        $readme = $this->readProjectFile('README.md');

        $this->assertStringContainsString('Laravel Vandar', $readme);
        $this->assertStringContainsString('[English Document](docs/en/README.md) | [Persian Document](docs/fa/README.md)', $readme);
        $this->assertStringContainsString('unofficial Laravel package', $readme);
        $this->assertStringContainsString('This package is unofficial and is not affiliated with Vandar.', $readme);
        $this->assertStringContainsString('payment workflow, verification, invoices, wallets, and reconciliation remain the responsibility of the application', $readme);
        $this->assertDoesNotMatchRegularExpression('/[\x{0600}-\x{06FF}]/u', $readme);
        $this->assertDoesNotMatchRegularExpression('/^##\s/m', $readme);
        $this->assertStringNotContainsString('## Features', $readme);
        $this->assertStringNotContainsString('## Installation', $readme);
        $this->assertStringNotContainsString('## Configuration', $readme);
        $this->assertStringNotContainsString('VANDAR_', $readme);
        $this->assertStringNotContainsString('endpoint-support.md', $readme);
        $this->assertStringNotContainsString('```php', $readme);
    }

    public function test_bilingual_documentation_files_exist(): void
    {
        $this->assertFileExists($this->projectPath('docs/en/README.md'));
        $this->assertFileExists($this->projectPath('docs/fa/README.md'));

        foreach ($this->docFiles as $file) {
            $this->assertFileExists($this->projectPath("docs/en/{$file}"));
            $this->assertFileExists($this->projectPath("docs/fa/{$file}"));
        }
    }

    public function test_legacy_documentation_links_remain_usable(): void
    {
        foreach ($this->docFiles as $file) {
            $legacy = $this->readProjectFile("docs/{$file}");

            $this->assertStringContainsString("en/{$file}", $legacy);
            $this->assertStringContainsString("fa/{$file}", $legacy);
            $this->assertStringContainsString('English Document', $legacy);
            $this->assertStringContainsString('Persian Document', $legacy);
        }
    }

    public function test_english_docs_keep_safety_and_boundary_guidance(): void
    {
        $docs = implode("\n", [
            $this->readProjectFile('docs/en/README.md'),
            $this->readProjectFile('docs/en/usage.md'),
            $this->readProjectFile('docs/en/security.md'),
            $this->readProjectFile('docs/en/production-checklist.md'),
            $this->readProjectFile('docs/en/laravel-payment-integration.md'),
        ]);

        $this->assertStringContainsString('defensive best effort', $docs);
        $this->assertStringContainsString('Do not log raw sensitive API responses directly in production', $docs);
        $this->assertStringContainsString('VANDAR_AUTO_REFRESH=false', $docs);
        $this->assertStringContainsString('Auto-refresh does not retry every failed API request', $docs);
        $this->assertStringContainsString('scheduled `vandar:refresh-token` command', $docs);
        $this->assertStringContainsString('VANDAR_ACCESS_TOKEN_EXPIRES_AT', $docs);
        $this->assertStringContainsString('VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false', $docs);
        $this->assertStringContainsString('VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path', $docs);
        $this->assertStringContainsString('VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented', $docs);
        $this->assertStringContainsString('must be manually verified against the real Vandar API before production use', $docs);
        $this->assertStringContainsString('verifyCallback()', $docs);
        $this->assertStringContainsString('redactedBody()', $docs);
        $this->assertStringContainsString('The package is not a complete payment workflow', $docs);
        $this->assertStringContainsString('It does not create payment tables', $docs);
    }

    public function test_persian_docs_keep_safety_and_boundary_guidance(): void
    {
        $docs = implode("\n", [
            $this->readProjectFile('docs/fa/README.md'),
            $this->readProjectFile('docs/fa/usage.md'),
            $this->readProjectFile('docs/fa/security.md'),
            $this->readProjectFile('docs/fa/production-checklist.md'),
            $this->readProjectFile('docs/fa/laravel-payment-integration.md'),
        ]);

        $this->assertStringContainsString('این پکیج فقط client/SDK است', $docs);
        $this->assertStringContainsString('defensive best effort', $docs);
        $this->assertStringContainsString('response خام و حساس API را در production مستقیم لاگ نکنید', $docs);
        $this->assertStringContainsString('VANDAR_AUTO_REFRESH=false', $docs);
        $this->assertStringContainsString('auto-refresh هر API request ناموفق را دوباره retry نمی‌کند', $docs);
        $this->assertStringContainsString('vandar:refresh-token', $docs);
        $this->assertStringContainsString('VANDAR_ACCESS_TOKEN_EXPIRES_AT', $docs);
        $this->assertStringContainsString('VANDAR_PERSIST_CONFIG_FALLBACK_TO_CACHE=false', $docs);
        $this->assertStringContainsString('VANDAR_IBAN_DELETE_ENDPOINT_STYLE=path', $docs);
        $this->assertStringContainsString('VANDAR_IBAN_DELETE_ENDPOINT_STYLE=documented', $docs);
        $this->assertStringContainsString('قبل از production حتما با API واقعی Vandar تست کنید', $docs);
        $this->assertStringContainsString('verifyCallback()', $docs);
        $this->assertStringContainsString('redactedBody()', $docs);
    }

    public function test_endpoint_support_keeps_ravand_as_future_work_in_both_languages(): void
    {
        $english = $this->readProjectFile('docs/en/endpoint-support.md');
        $persian = $this->readProjectFile('docs/fa/endpoint-support.md');

        $this->assertStringContainsString('| Ravand | Official Ravand endpoint group | none | future module |', $english);
        $this->assertStringContainsString('Customer cards are supported.', $english);
        $this->assertStringContainsString('Subscription / Direct Debit endpoints are supported', $english);
        $this->assertStringContainsString('Ravand فعلا future module است', $persian);
        $this->assertStringContainsString('| Ravand | Official Ravand endpoint group | none | future module |', $persian);
    }

    public function test_language_indexes_link_to_each_other_and_main_pages(): void
    {
        $english = $this->readProjectFile('docs/en/README.md');
        $persian = $this->readProjectFile('docs/fa/README.md');

        $this->assertStringContainsString('[English](README.md) | [فارسی](../fa/README.md)', $english);
        $this->assertStringContainsString('[فارسی](README.md) | [English](../en/README.md)', $persian);

        foreach ($this->docFiles as $file) {
            $this->assertStringContainsString($file, $english);
            $this->assertStringContainsString($file, $persian);
        }
    }

    public function test_language_switches_are_clickable_markdown_links(): void
    {
        foreach ($this->docFiles as $file) {
            $english = $this->readProjectFile("docs/en/{$file}");
            $persian = $this->readProjectFile("docs/fa/{$file}");

            $this->assertStringContainsString("[English]({$file}) | [فارسی](../fa/{$file})", $english);
            $this->assertStringContainsString("[فارسی]({$file}) | [English](../en/{$file})", $persian);
            $this->assertStringNotContainsString("English: {$file}", $english);
            $this->assertStringNotContainsString("فارسی: {$file}", $persian);
        }
    }

    private function readProjectFile(string $path): string
    {
        return (string) file_get_contents($this->projectPath($path));
    }

    private function projectPath(string $path): string
    {
        return dirname(__DIR__, 2).DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $path);
    }
}
