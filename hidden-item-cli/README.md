# Task 2: Hidden Item Game CLI Program

This directory contains the solution for **Task 2: Hidden Item Game**, written as a standalone command-line PHP script.

---

## Game Setup & Rules
The game board is defined by a 6x8 grid:
- `#` represents an obstacle.
- `.` represents a clear path.
- `X` represents the player's starting position (located at Row 4, Col 1).

The player must navigate in this exact order:
1. **North / Up** by `A` steps (where `A >= 1`)
2. **East / Right** by `B` steps (where `B >= 1`)
3. **South / Down** by `C` steps (where `C >= 1`)

A traversal path is only valid if **all intermediate steps** are on clear paths (`.`).

---

## Coordinate Systems

To ensure clarity and prevent any ambiguity, the program outputs coordinates in two formats:
1. **Grid Coordinates**: 0-indexed `(Row, Column)` starting from the top-left corner `(0, 0)`.
2. **Cartesian Coordinates**: 1-indexed `(X, Y)` starting from the bottom-left corner `(1, 1)`.
   - Column 0 maps to `X = 1`.
   - Row 5 (bottom) maps to `Y = 1`.
   - Column 7 maps to `X = 8`.
   - Row 0 (top) maps to `Y = 6`.

---

## How to Run the Program

No external dependencies are required. Simply run the script with PHP from the command line:

```bash
php hidden_item.php
```

### Example Output
```
=== HIDDEN ITEM GAME SOLVER ===

Starting position: X at Grid(Row: 4, Col: 1) | Cartesian(X: 2, Y: 2)

Initial Grid Layout:
########
#......#
#.###..#
#...#.##
#X#....#
########

Reachable Probable Coordinates:
1. Grid(Row: 4, Col: 3) | Cartesian(X: 4, Y: 2)
   Reachable via 1 path(s):
     - Move North: 1 step(s), East: 2 step(s), South: 1 step(s)
2. Grid(Row: 2, Col: 5) | Cartesian(X: 6, Y: 4)
   Reachable via 1 path(s):
     - Move North: 3 step(s), East: 4 step(s), South: 1 step(s)
3. Grid(Row: 3, Col: 5) | Cartesian(X: 6, Y: 3)
   Reachable via 1 path(s):
     - Move North: 3 step(s), East: 4 step(s), South: 2 step(s)
4. Grid(Row: 4, Col: 5) | Cartesian(X: 6, Y: 2)
   Reachable via 1 path(s):
     - Move North: 3 step(s), East: 4 step(s), South: 3 step(s)
5. Grid(Row: 2, Col: 6) | Cartesian(X: 7, Y: 4)
   Reachable via 1 path(s):
     - Move North: 3 step(s), East: 5 step(s), South: 1 step(s)

Grid with Probable Locations Marked ($):
########
#......#
#.###$$#
#...#$##
#X#$.$.#
########
```
