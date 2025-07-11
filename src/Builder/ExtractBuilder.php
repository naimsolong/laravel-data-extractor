<?php

namespace NaimSolong\DataExtractor\Builder;

use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;
use RuntimeException;

class ExtractBuilder
{
    public const FORMAT_CSV = 'csv';

    public const FORMAT_SQL = 'sql';

    public const DEFAULT_FORMAT = self::FORMAT_SQL;

    public const FORMATS = [
        self::FORMAT_CSV,
        self::FORMAT_SQL,
    ];

    /**
     * The model instance.
     */
    protected mixed $model;

    protected CsvBuilder|SqlBuilder $builder;

    public function createBuilder(string $type): self
    {
        if (! in_array($type, self::FORMATS, true)) {
            throw new InvalidArgumentException("Invalid builder type: {$type}");
        }

        $this->builder = match ($type) {
            self::FORMAT_CSV => new CsvBuilder,
            self::FORMAT_SQL => new SqlBuilder,
            default => throw new InvalidArgumentException("Unsupported builder type: {$type}"),
        };

        return $this;
    }

    public function asCsv(): self
    {
        return $this->createBuilder(self::FORMAT_CSV);
    }

    public function asSql(): self
    {
        return $this->createBuilder(self::FORMAT_SQL);
    }

    /**
     * Get the model instance.
     */
    public function getModel(): mixed
    {
        return $this->model;
    }

    /**
     * Set the model instance.
     */
    public function setModel(mixed $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function build(): string
    {
        if (! isset($this->builder)) {
            throw new RuntimeException('Builder not initialized. Call createBuilder(), asCsv() or asSql() first.');
        }

        if (! isset($this->model)) {
            throw new RuntimeException('Model not set. Use setModel() to set the model before building.');
        }

        $table = $this->model->getTable();

        $columns = app(DatabaseManager::class)
            ->connection($this->model->getConnectionName())
            ->getSchemaBuilder()
            ->getColumnListing($table);

        $this->builder
            ->setSchemaName($table)
            ->setColumns($columns)
            ->setData($this->model->toArray());

        return $this->builder->build();
    }
}
