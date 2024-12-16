<?php

namespace SchenkeIo\PackagingTools;

use Laravel\Prompts\SearchPrompt;
use SchenkeIo\PackagingTools\Setup\Composer;
use SchenkeIo\PackagingTools\Setup\Config;

class DeveloperMenu
{
    public static function handle(): void
    {
        $commands = array_merge(
            Composer::getCommands(),
            self::getArtisanCommands()
        );
        $command = (new SearchPrompt(
            label: 'Search for the user that should receive the mail',
            options: fn ($search) => array_filter($commands, fn ($command) => str_contains($command, $search))

        ))->prompt();
        if ($command) {
            echo "**$command**\n";

            try {
                self::runShellCommand($command);
            } catch (\RuntimeException $e) {
                echo 'Error: '.$e->getMessage()."\n";
                exit(1); // Indicate failure to Composer
            }
        }
    }

    private static function getArtisanCommands(): array
    {
        $return = [];
        foreach ((new Config)->config->dev as $command) {
            $return["php artisan $command"] = $command;
        }

        return $return;
    }

    public static function runShellCommand($command): int
    {
        passthru($command, $return_value);

        return $return_value;
    }
}
