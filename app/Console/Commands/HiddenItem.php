<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class HiddenItem extends Command
{
    protected $signature = 'game:hidden-item
                            {--A=1 : Steps to move North}
                            {--B=2 : Steps to move East}
                            {--C=1 : Steps to move South}';

    protected $description = 'Find the hidden item by navigating North → East → South';

    /**
     * The game grid. # = obstacle, . = walkable, X = player start.
     */
    private array $grid = [
        ['#','#','#','#','#','#','#','#'],
        ['#','.','.','.','.','.','.','#'],
        ['#','.','#','#','#','.','.','#'],
        ['#','.','.','.','#','.','#','#'],
        ['#','X','#','.','.','.','.','#'],
        ['#','#','#','#','#','#','#','#'],
    ];

    public function handle(): int
    {
        $a = (int) $this->option('A');
        $b = (int) $this->option('B');
        $c = (int) $this->option('C');

        // Find the player starting position (X).
        [$startRow, $startCol] = $this->findStart();

        $this->info("Grid:");
        $this->printGrid($this->grid);

        $this->newLine();
        $this->info("Player starts at: (col={$startCol}, row={$startRow})");
        $this->info("Movement: North {$a} → East {$b} → South {$c}");
        $this->newLine();

        // Explore all possible paths with partial movement allowed.
        $possibleLocations = $this->findPossibleLocations($startRow, $startCol, $a, $b, $c);

        if (empty($possibleLocations)) {
            $this->warn("No possible locations found — all paths are blocked.");
            return self::SUCCESS;
        }

        $this->info("Possible locations:");
        foreach ($possibleLocations as [$row, $col]) {
            $this->line("  (col={$col}, row={$row})");
        }

        // Bonus: display grid with $ markers.
        $this->newLine();
        $this->info("Grid with possible locations marked ($):");
        $this->printGrid($this->buildMarkedGrid($possibleLocations));

        return self::SUCCESS;
    }

    /**
     * Find all walkable cells the player could reach after moving:
     *   1. Up to A steps North (stop early if blocked).
     *   2. Up to B steps East  (stop early if blocked).
     *   3. Up to C steps South (stop early if blocked).
     *
     * Every intermediate stopping point is a valid candidate because
     * the item could be placed at any walkable cell the player visits.
     */
    private function findPossibleLocations(
    int $startRow, int $startCol,
    int $a, int $b, int $c
): array {
    $locations = [];

    // Phase 1 — move North exactly A steps (stop early if blocked).
    $row = $startRow;
    for ($step = 1; $step <= $a; $step++) {
        $nextRow = $row - 1;
        if (! $this->isWalkable($nextRow, $startCol)) break;
        $row = $nextRow;
    }
    $afterNorth = [$row, $startCol];

    // Phase 2 — move East exactly B steps (stop early if blocked).
    $col = $afterNorth[1];
    $row = $afterNorth[0];
    for ($step = 1; $step <= $b; $step++) {
        $nextCol = $col + 1;
        if (! $this->isWalkable($row, $nextCol)) break;
        $col = $nextCol;
    }
    $afterEast = [$row, $col];

    // Phase 3 — move South exactly C steps (stop early if blocked).
    $row = $afterEast[0];
    $col = $afterEast[1];
    for ($step = 1; $step <= $c; $step++) {
        $nextRow = $row + 1;
        if (! $this->isWalkable($nextRow, $col)) break;
        $row = $nextRow;
    }

    $locations[] = [$row, $col];

    return $locations;
}
    /**
     * Determine whether a cell exists and is walkable (. or X).
     */
    private function isWalkable(int $row, int $col): bool
    {
        return isset($this->grid[$row][$col])
            && in_array($this->grid[$row][$col], ['.', 'X'], true);
    }

    /**
     * Find the row and column of the player's starting position (X).
     */
    private function findStart(): array
    {
        foreach ($this->grid as $rowIndex => $cols) {
            foreach ($cols as $colIndex => $cell) {
                if ($cell === 'X') {
                    return [$rowIndex, $colIndex];
                }
            }
        }
        throw new \RuntimeException('No starting position (X) found in grid.');
    }

    /**
     * Build a copy of the grid with $ symbols at the given locations.
     */
    private function buildMarkedGrid(array $locations): array
    {
        $marked = $this->grid;
        foreach ($locations as [$row, $col]) {
            if ($marked[$row][$col] === '.') {
                $marked[$row][$col] = '$';
            }
        }
        return $marked;
    }

    /**
     * Print a grid to the console.
     */
    private function printGrid(array $grid): void
    {
        foreach ($grid as $row) {
            $this->line('  ' . implode('', $row));
        }
    }
}