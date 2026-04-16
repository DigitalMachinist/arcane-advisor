<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

test('Pest uses in-memory SQLite when APP_ENV=testing', function (): void {
    expect(config('app.env'))->toBe('testing')
        ->and(DB::connection()->getDriverName())->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:');

    // Prove the database is actually usable by creating and querying a table.
    DB::statement('CREATE TABLE pr_0_4_sqlite_probe (id INTEGER PRIMARY KEY, label TEXT)');
    DB::insert('INSERT INTO pr_0_4_sqlite_probe (id, label) VALUES (?, ?)', [1, 'arcane']);

    $row = DB::selectOne('SELECT label FROM pr_0_4_sqlite_probe WHERE id = 1');

    expect($row?->label)->toBe('arcane');
});
