<?php

use SchenkeIo\PackagingTools\Contracts\SetupDefinitionInterface;
use SchenkeIo\PackagingTools\Setup\TaskRegistry;

describe('TaskRegistry', function () {
    it('can register a task', function () {
        $registry = new TaskRegistry(false);
        $mockTask = Mockery::mock(SetupDefinitionInterface::class);
        $registry->registerTask('test_task', $mockTask);
        expect($registry->getTask('test_task'))->toBe($mockTask);
    });

    it('can retrieve all tasks', function () {
        $registry = new TaskRegistry(false);
        $mockTask1 = Mockery::mock(SetupDefinitionInterface::class);
        $mockTask2 = Mockery::mock(SetupDefinitionInterface::class);
        $registry->registerTask('task1', $mockTask1);
        $registry->registerTask('task2', $mockTask2);

        $tasks = $registry->getAllTasks();

        expect($tasks)->toBeArray();
        expect($tasks)->toHaveCount(2);
        expect($tasks['task1'])->toBe($mockTask1);
        expect($tasks['task2'])->toBe($mockTask2);
    });

    it('can retrieve a specific task by name', function () {
        $registry = new TaskRegistry(false);
        $mockTask = Mockery::mock(SetupDefinitionInterface::class);
        $registry->registerTask('test_task', $mockTask);

        $retrievedTask = $registry->getTask('test_task');

        expect($retrievedTask)->toBe($mockTask);
    });

    it('returns null when retrieving a non-existent task', function () {
        $registry = new TaskRegistry(false);
        $task = $registry->getTask('non_existent_task');

        expect($task)->toBeNull();
    });

    it('constructs and registers definitions', function () {
        $registry = new TaskRegistry;

        $tasks = $registry->getAllTasks();

        // Depending on environment, could be empty or not, but it should be an array.
        expect($tasks)->toBeArray();
    });

    it('registers core tasks with kebab-case names', function () {
        $registry = new TaskRegistry;
        $tasks = $registry->getAllTasks();

        expect($tasks)->toHaveKeys(['sql-cache', 'analyse', 'coverage']);
        expect($tasks)->not->toHaveKey('SqlCache');
    });
});
