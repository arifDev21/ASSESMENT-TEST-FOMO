<?php

function color($text, $colorCode) {
    return "\033[{$colorCode}m{$text}\033[0m";
}

function bold($text) {
    return color($text, '1');
}

function success($text) {
    return color($text, '32');
}

function warning($text) {
    return color($text, '33');
}

function info($text) {
    return color($text, '36');
}

$grid = [
    '########',
    '#......#',
    '#.###..#',
    '#...#.##',
    '#X#....#',
    '########',
];

$rows = count($grid);
$cols = strlen($grid[0]);

$startX = -1;
$startY = -1;

for ($r = 0; $r < $rows; $r++) {
    for ($c = 0; $c < $cols; $c++) {
        if ($grid[$r][$c] === 'X') {
            $startY = $r;
            $startX = $c;
            break 2;
        }
    }
}

if ($startX === -1 || $startY === -1) {
    echo color("Error: Player starting position 'X' not found in grid.\n", '31');
    exit(1);
}

echo bold("\n=== HIDDEN ITEM GAME SOLVER ===\n\n");
echo "Starting position: " . info("X") . " at " . bold("Grid(Row: {$startY}, Col: {$startX})") . " | " . bold("Cartesian(X: " . ($startX + 1) . ", Y: " . ($rows - $startY) . ")\n\n");

echo bold("Initial Grid Layout:\n");
foreach ($grid as $row) {
    for ($c = 0; $c < strlen($row); $c++) {
        $char = $row[$c];
        if ($char === '#') {
            echo color($char, '90');
        } elseif ($char === 'X') {
            echo color($char, '31;1');
        } else {
            echo $char;
        }
    }
    echo "\n";
}
echo "\n";

$solutions = [];

for ($a = 1; $a < $rows; $a++) {
    $validA = true;
    for ($i = 1; $i <= $a; $i++) {
        $currRow = $startY - $i;
        if ($currRow < 0 || $grid[$currRow][$startX] !== '.') {
            $validA = false;
            break;
        }
    }
    
    if (!$validA) continue;
    
    $p1Row = $startY - $a;
    $p1Col = $startX;

    for ($b = 1; $b < $cols; $b++) {
        $validB = true;
        for ($j = 1; $j <= $b; $j++) {
            $currCol = $p1Col + $j;
            if ($currCol >= $cols || $grid[$p1Row][$currCol] !== '.') {
                $validB = false;
                break;
            }
        }
        
        if (!$validB) continue;
        
        $p2Row = $p1Row;
        $p2Col = $p1Col + $b;

        for ($c = 1; $c < $rows; $c++) {
            $validC = true;
            for ($k = 1; $k <= $c; $k++) {
                $currRow = $p2Row + $k;
                if ($currRow >= $rows || $grid[$currRow][$p2Col] !== '.') {
                    $validC = false;
                    break;
                }
            }
            
            if (!$validC) continue;
            
            $p3Row = $p2Row + $c;
            $p3Col = $p2Col;

            $solutions[] = [
                'steps' => ['A' => $a, 'B' => $b, 'C' => $c],
                'grid' => ['row' => $p3Row, 'col' => $p3Col],
                'cartesian' => ['x' => $p3Col + 1, 'y' => $rows - $p3Row]
            ];
        }
    }
}

echo bold("Reachable Probable Coordinates:\n");
if (empty($solutions)) {
    echo warning("No valid coordinate paths found.\n");
} else {
    $uniqueCoords = [];
    foreach ($solutions as $sol) {
        $key = $sol['grid']['row'] . ',' . $sol['grid']['col'];
        if (!isset($uniqueCoords[$key])) {
            $uniqueCoords[$key] = [
                'grid' => $sol['grid'],
                'cartesian' => $sol['cartesian'],
                'paths' => []
            ];
        }
        $uniqueCoords[$key]['paths'][] = $sol['steps'];
    }

    $index = 1;
    foreach ($uniqueCoords as $coord) {
        $gridStr = "Grid(Row: {$coord['grid']['row']}, Col: {$coord['grid']['col']})";
        $cartesianStr = "Cartesian(X: {$coord['cartesian']['x']}, Y: {$coord['cartesian']['y']})";
        
        echo "{$index}. " . success($gridStr) . " | " . success($cartesianStr) . "\n";
        echo "   Reachable via " . count($coord['paths']) . " path(s):\n";
        foreach ($coord['paths'] as $path) {
            echo "     - Move North: " . info($path['A']) . " step(s), East: " . info($path['B']) . " step(s), South: " . info($path['C']) . " step(s)\n";
        }
        $index++;
    }
    echo "\n";

    echo bold("Grid with Probable Locations Marked ($):\n");
    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {
            $key = $r . ',' . $c;
            if (isset($uniqueCoords[$key])) {
                echo success('$');
            } else {
                $char = $grid[$r][$c];
                if ($char === '#') {
                    echo color($char, '90');
                } elseif ($char === 'X') {
                    echo color($char, '31;1');
                } else {
                    echo $char;
                }
            }
        }
        echo "\n";
    }
    echo "\n";
}
