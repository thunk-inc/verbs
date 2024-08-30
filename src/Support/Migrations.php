<?php

namespace Thunk\Verbs\Support;

use ReflectionClass;
use Thunk\Verbs\Exceptions\MigratorException;
use Thunk\Verbs\ShouldMigrateData;

abstract class Migrations implements ShouldMigrateData
{
    /**
     * @throws MigratorException
     */
    public function migrations(): array
    {
        $migrations = [];

        $methods = (new ReflectionClass($this))->getMethods();
        foreach ($methods as $method) {
            if (preg_match('/^v(\d+)/', $method->name, $matches)) {
                $version = (int) $matches[1];
                if (isset($migrations[$version])) {
                    throw new MigratorException("Duplicate migration version: {$method->name} matches another migration with the same version number.");
                }
                $migrations[$version] = $method->getClosure($this);
            }
        }

        return $migrations;
    }
}
