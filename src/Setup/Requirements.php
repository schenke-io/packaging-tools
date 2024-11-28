<?php

namespace SchenkeIo\PackagingTools\Setup;

class Requirements
{
    protected array $data = [];

    public static function dev(string $name): self
    {
        $me = new self;
        $me->addRequireDev($name);

        return $me;
    }

    public static function require(string $name): self
    {
        $me = new self;
        $me->addRequire($name);

        return $me;
    }

    public function addRequire(string $name): void
    {
        $this->data['require'][] = $name;
    }

    public function addRequireDev(string $name): void
    {
        $this->data['require-dev'][] = $name;
    }

    public function addRequirements(Requirements $requirements): void
    {
        foreach ($requirements->data as $key => $value) {
            $this->data[$key] = $value;
        }
    }

    public function data(): array
    {
        return $this->data;
    }

    public function removeRequire(string $name): void
    {
        foreach (['require', 'require-dev'] as $key) {
            if (($index = array_search($name, $this->data[$key])) !== false) {
                unset($this->data[$key][$index]);
                $this->data[$key] = array_values($this->data[$key]);
            }
        }
    }
}
