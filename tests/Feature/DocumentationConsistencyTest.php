<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Tests\Feature;

use Zarbinco\LaravelVandar\Tests\TestCase;

final class DocumentationConsistencyTest extends TestCase
{
    public function test_readme_links_endpoint_support_and_documents_safe_ipg_flow(): void
    {
        $readme = $this->readProjectFile('README.md');

        $this->assertStringContainsString('docs/endpoint-support.md', $readme);
        $this->assertStringContainsString('IPG callback status is not final payment success', $readme);
        $this->assertStringContainsString('verifyCallback()', $readme);
        $this->assertStringContainsString('callbackHasOkStatus()', $readme);
        $this->assertStringContainsString('does not create application payment workflows', $readme);
        $this->assertStringContainsString('VANDAR_RETRY_MONEY_MOVING_REQUESTS=false', $readme);

        preg_match('/## Features(?P<features>.*?)## Requirements/s', $readme, $matches);

        $this->assertStringNotContainsString('Ravand', (string) ($matches['features'] ?? ''));
    }

    public function test_release_readiness_documents_exist(): void
    {
        $this->assertFileExists($this->projectPath('UPGRADE.md'));
        $this->assertFileExists($this->projectPath('docs/release-checklist.md'));
        $this->assertFileExists($this->projectPath('docs/production-checklist.md'));
    }

    public function test_endpoint_support_keeps_ravand_as_future_work(): void
    {
        $matrix = $this->readProjectFile('docs/endpoint-support.md');

        $this->assertStringContainsString('| Ravand | Official Ravand endpoint group | none | future module |', $matrix);
        $this->assertStringContainsString('Customer cards are supported.', $matrix);
        $this->assertStringContainsString('Subscription / Direct Debit endpoints are supported', $matrix);
    }

    public function test_security_and_usage_docs_include_safe_response_and_retry_guidance(): void
    {
        $docs = implode("\n", [
            $this->readProjectFile('README.md'),
            $this->readProjectFile('UPGRADE.md'),
            $this->readProjectFile('docs/security.md'),
            $this->readProjectFile('docs/usage.md'),
            $this->readProjectFile('docs/production-checklist.md'),
        ]);

        $this->assertStringContainsString('verifyCallback()', $docs);
        $this->assertStringContainsString('redactedBody()', $docs);
        $this->assertStringContainsString('VANDAR_RETRY_MONEY_MOVING_REQUESTS=false', $docs);
        $this->assertStringContainsString('Do not log `$response->toArray()` directly', $docs);
        $this->assertStringContainsString('Package exception context is redacted', $docs);
        $this->assertStringContainsString('parsed JSON and headers may still contain sensitive values', $docs);
    }

    public function test_production_docs_include_token_cache_and_uncertain_money_moving_guidance(): void
    {
        $docs = implode("\n", [
            $this->readProjectFile('docs/security.md'),
            $this->readProjectFile('docs/usage.md'),
            $this->readProjectFile('docs/production-checklist.md'),
        ]);

        $this->assertStringContainsString('shared cache such as Redis', $docs);
        $this->assertStringContainsString('Do not use file cache as the token store across multiple servers', $docs);
        $this->assertStringContainsString('Treat timeouts and unknown responses from money-moving requests as unknown state', $docs);
        $this->assertStringContainsString('Keep `VANDAR_HTTP_VERIFY_SSL=true`', $docs);
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
