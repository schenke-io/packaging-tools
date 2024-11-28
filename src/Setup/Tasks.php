<?php

namespace SchenkeIo\PackagingTools\Setup;

enum Tasks: string
{
    case Analyse = 'analyse';
    case Coverage = 'coverage';
    case Markdown = 'markdown';
    case Pint = 'pint';
    case Test = 'test';

    // groups

    case Check = 'check';
    case Release = 'release';

    public function definition(): Definition
    {
        return match ($this) {
            self::Analyse => new Definitions\AnalyseDefinition,
            self::Coverage => new Definitions\CoverageDefinition,
            self::Markdown => new Definitions\MarkdownDefinition,
            self::Pint => new Definitions\PintDefinition,
            self::Test => new Definitions\TestDefinition,
            self::Check => new Definitions\GroupDefinition(['pint', 'test', 'markdown']),
            self::Release => new Definitions\GroupDefinition(['pint', 'analyse', 'coverage', 'markdown']),
        };
    }

    public function commands(Config $config): array|string
    {
        return $this->definition()->commands($config);
    }

    public function packages(Config $config): Requirements
    {
        return $this->definition()->packages($config);
    }
}
