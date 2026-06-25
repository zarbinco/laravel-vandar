<?php

declare(strict_types=1);

namespace Zarbinco\LaravelVandar\Commands;

use Illuminate\Console\Command;
use Throwable;
use Zarbinco\LaravelVandar\Exceptions\VandarException;
use Zarbinco\LaravelVandar\Token\TokenManager;

final class VandarRefreshTokenCommand extends Command
{
    protected $signature = 'vandar:refresh-token';

    protected $description = 'Refresh the configured Vandar access token.';

    public function handle(TokenManager $tokens): int
    {
        try {
            $tokenSet = $tokens->refresh(force: true);
        } catch (VandarException $exception) {
            $this->error('Unable to refresh Vandar token: '.$exception->getMessage());

            return self::FAILURE;
        } catch (Throwable) {
            $this->error('Unable to refresh Vandar token.');

            return self::FAILURE;
        }

        $this->info('Vandar token refreshed successfully.');
        $this->line('Token type: '.($tokenSet->tokenType ?: 'Bearer'));

        if ($tokenSet->expiresAt !== null) {
            $this->line('Expires at: '.$tokenSet->expiresAt->toIso8601String());
        }

        return self::SUCCESS;
    }
}
