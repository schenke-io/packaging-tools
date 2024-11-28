<?php

namespace SchenkeIo\PackagingTools\Setup;

use Illuminate\Filesystem\Filesystem;

class Composer extends Base
{
    /**
     * @var array<string,mixed>
     */
    public array $composer = [];

    protected bool $isDirty = false;

    /**
     * @var array<int,string>
     */
    protected array $neededPackages = [];

    public Requirements $requirements;

    /**
     * @throws \Exception
     */
    public function __construct(protected Filesystem $filesystem = new Filesystem)
    {
        parent::__construct($this->filesystem);
        $this->requirements = new Requirements;
        $this->composer = json_decode($this->composerjsonContent, true);
        /*
         * add some special commands
         */
        $this->composer['scripts']['low'] = 'composer update --prefer-lowest --prefer-dist';
        $this->composer['scripts']['stable'] = 'composer update --prefer-stable --prefer-dist';
    }

    public function save(): void
    {
        $this->filesystem->put(
            $this->composerJsonPath,
            json_encode(
                $this->composer,
                JSON_PRETTY_PRINT + JSON_UNESCAPED_SLASHES
            )
        );
    }

    public static function packageFound(string $packageWanted, ?string $key = null): bool
    {
        $me = new self;
        $sources = is_null($key) ? ['require', 'require-dev'] : [$key];
        foreach ($sources as $source) {
            foreach ($me->composer[$source] ?? [] as $packageName => $package) {
                if ($packageWanted == $packageName) {
                    return true;
                }
            }
        }

        return false;
    }

    public function setCommands(Tasks $task, Config $config): void
    {
        $key = $task->value;
        $value = $task->definition()->commands($config);
        $this->composer['scripts'][$key] = $value;
    }

    public function setPackages(Tasks $task, Config $config): void
    {
        $packages = $task->definition()->packages($config)->data();
        foreach ($packages as $key => $names) {
            foreach ($names as $name) {
                if (str_ends_with($key, '-dev')) {
                    $this->neededPackages[] = "composer require --dev $name";
                } else {
                    $this->neededPackages[] = "composer require $name";
                }
            }
        }
    }

    public function setAddPackages(): void
    {
        $this->composer['scripts']['add'] = array_unique($this->neededPackages);
    }
}
