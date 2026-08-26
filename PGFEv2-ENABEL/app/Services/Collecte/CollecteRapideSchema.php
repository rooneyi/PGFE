<?php

declare(strict_types=1);

namespace App\Services\Collecte;

final class CollecteRapideSchema
{
    /** @return array<int, array<string, mixed>> */
    public function steps(): array
    {
        return config('collecte_rapide.steps', []);
    }

    /** @return array<string, string> */
    public function regimes(): array
    {
        return config('collecte_rapide.regimes', []);
    }

    /** @return array<string, string> */
    public function teachingTypes(): array
    {
        return config('collecte_rapide.teaching_types', []);
    }

    public function step(int $step): ?array
    {
        return $this->steps()[$step] ?? null;
    }

    public function lastStep(): int
    {
        return (int) max(array_keys($this->steps()));
    }

    /** @return array<string, mixed> */
    public function emptyPayload(): array
    {
        $data = [];

        foreach ($this->steps() as $step => $meta) {
            $key = $meta['key'] ?? null;
            if (! is_string($key) || in_array($key, ['contexte', 'recap'], true)) {
                continue;
            }
            $data[$key] = $this->emptyMatrixForStep($step);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    public function emptyMatrixForStep(int $step): array
    {
        $meta = $this->step($step);
        if ($meta === null) {
            return [];
        }

        $kind = $meta['kind'] ?? null;
        $columns = array_keys($meta['columns'] ?? []);

        return match ($kind) {
            'simple_matrix' => $this->emptySimple($columns),
            'gender_matrix' => $this->emptyGender($columns, $meta['sex_keys'] ?? ['G', 'F']),
            'type_matrix' => $this->emptyType($columns),
            default => [],
        };
    }

    /**
     * Merge + sanitize step payload and keep other steps intact.
     *
     * @param  array<string, mixed>  $existing
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public function mergeStepData(array $existing, int $step, array $incoming): array
    {
        $meta = $this->step($step);
        if ($meta === null) {
            return $existing;
        }

        $key = $meta['key'] ?? null;
        if (! is_string($key) || in_array($key, ['contexte', 'recap'], true)) {
            return $existing;
        }

        $existing[$key] = $this->sanitizeMatrix($step, $incoming[$key] ?? $incoming);

        return $existing;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function sanitizeMatrix(int $step, array $raw): array
    {
        $empty = $this->emptyMatrixForStep($step);
        $meta = $this->step($step);
        $kind = $meta['kind'] ?? null;

        return match ($kind) {
            'simple_matrix' => $this->sanitizeSimple($empty, $raw),
            'gender_matrix' => $this->sanitizeGender($empty, $raw, $meta['sex_keys'] ?? ['G', 'F']),
            'type_matrix' => $this->sanitizeType($empty, $raw),
            default => $empty,
        };
    }

    /**
     * Aggregate several payloads (submitted SD collectes) into one.
     *
     * @param  list<array<string, mixed>>  $payloads
     * @return array<string, mixed>
     */
    public function aggregate(array $payloads): array
    {
        $result = $this->emptyPayload();

        foreach ($payloads as $payload) {
            if (! is_array($payload)) {
                continue;
            }
            foreach ($this->steps() as $step => $meta) {
                $key = $meta['key'] ?? null;
                if (! is_string($key) || ! isset($result[$key], $payload[$key])) {
                    continue;
                }
                $result[$key] = $this->sumMatrices(
                    $result[$key],
                    $this->sanitizeMatrix($step, is_array($payload[$key]) ? $payload[$key] : []),
                    $meta['kind'] ?? null,
                    $meta['sex_keys'] ?? ['G', 'F']
                );
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $columns
     * @return array<string, array<string, int>>
     */
    private function emptySimple(array $columns): array
    {
        $row = [];
        foreach ($columns as $col) {
            $row[$col] = 0;
        }

        $out = [];
        foreach (array_keys($this->regimes()) as $regime) {
            $out[$regime] = $row;
        }

        return $out;
    }

    /**
     * @param  list<string>  $columns
     * @param  list<string>  $sexKeys
     * @return array<string, array<string, array<string, int>>>
     */
    private function emptyGender(array $columns, array $sexKeys): array
    {
        $cell = [];
        foreach ($sexKeys as $sex) {
            $cell[$sex] = 0;
        }
        $row = [];
        foreach ($columns as $col) {
            $row[$col] = $cell;
        }

        $out = [];
        foreach (array_keys($this->regimes()) as $regime) {
            $out[$regime] = $row;
        }

        return $out;
    }

    /**
     * @param  list<string>  $columns
     * @return array<string, array<string, array<string, array<string, int>>>>
     */
    private function emptyType(array $columns): array
    {
        $sexCell = ['G' => 0, 'F' => 0];
        $yearRow = [];
        foreach ($columns as $col) {
            $yearRow[$col] = $sexCell;
        }

        $out = [];
        foreach (array_keys($this->regimes()) as $regime) {
            $types = [];
            foreach (array_keys($this->teachingTypes()) as $type) {
                $types[$type] = $yearRow;
            }
            $out[$regime] = $types;
        }

        return $out;
    }

    /**
     * @param  array<string, array<string, int>>  $empty
     * @param  array<string, mixed>  $raw
     * @return array<string, array<string, int>>
     */
    private function sanitizeSimple(array $empty, array $raw): array
    {
        foreach ($empty as $regime => $cols) {
            foreach ($cols as $col => $_) {
                $empty[$regime][$col] = $this->intVal($raw[$regime][$col] ?? 0);
            }
        }

        return $empty;
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $empty
     * @param  array<string, mixed>  $raw
     * @param  list<string>  $sexKeys
     * @return array<string, array<string, array<string, int>>>
     */
    private function sanitizeGender(array $empty, array $raw, array $sexKeys): array
    {
        foreach ($empty as $regime => $cols) {
            foreach ($cols as $col => $sexes) {
                foreach ($sexKeys as $sex) {
                    $empty[$regime][$col][$sex] = $this->intVal($raw[$regime][$col][$sex] ?? 0);
                }
            }
        }

        return $empty;
    }

    /**
     * @param  array<string, array<string, array<string, array<string, int>>>>  $empty
     * @param  array<string, mixed>  $raw
     * @return array<string, array<string, array<string, array<string, int>>>>
     */
    private function sanitizeType(array $empty, array $raw): array
    {
        foreach ($empty as $regime => $types) {
            foreach ($types as $type => $cols) {
                foreach ($cols as $col => $sexes) {
                    foreach (['G', 'F'] as $sex) {
                        $empty[$regime][$type][$col][$sex] = $this->intVal($raw[$regime][$type][$col][$sex] ?? 0);
                    }
                }
            }
        }

        return $empty;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     * @return array<string, mixed>
     */
    private function sumMatrices(array $a, array $b, ?string $kind, array $sexKeys): array
    {
        return match ($kind) {
            'simple_matrix' => $this->sumSimple($a, $b),
            'gender_matrix' => $this->sumGender($a, $b, $sexKeys),
            'type_matrix' => $this->sumType($a, $b),
            default => $a,
        };
    }

    /**
     * @param  array<string, array<string, int>>  $a
     * @param  array<string, array<string, int>>  $b
     * @return array<string, array<string, int>>
     */
    private function sumSimple(array $a, array $b): array
    {
        foreach ($a as $regime => $cols) {
            foreach ($cols as $col => $val) {
                $a[$regime][$col] = (int) $val + (int) ($b[$regime][$col] ?? 0);
            }
        }

        return $a;
    }

    /**
     * @param  array<string, array<string, array<string, int>>>  $a
     * @param  array<string, array<string, array<string, int>>>  $b
     * @param  list<string>  $sexKeys
     * @return array<string, array<string, array<string, int>>>
     */
    private function sumGender(array $a, array $b, array $sexKeys): array
    {
        foreach ($a as $regime => $cols) {
            foreach ($cols as $col => $sexes) {
                foreach ($sexKeys as $sex) {
                    $a[$regime][$col][$sex] = (int) ($sexes[$sex] ?? 0) + (int) ($b[$regime][$col][$sex] ?? 0);
                }
            }
        }

        return $a;
    }

    /**
     * @param  array<string, array<string, array<string, array<string, int>>>>  $a
     * @param  array<string, array<string, array<string, array<string, int>>>>  $b
     * @return array<string, array<string, array<string, array<string, int>>>>
     */
    private function sumType(array $a, array $b): array
    {
        foreach ($a as $regime => $types) {
            foreach ($types as $type => $cols) {
                foreach ($cols as $col => $sexes) {
                    foreach (['G', 'F'] as $sex) {
                        $a[$regime][$type][$col][$sex] = (int) ($sexes[$sex] ?? 0) + (int) ($b[$regime][$type][$col][$sex] ?? 0);
                    }
                }
            }
        }

        return $a;
    }

    private function intVal(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(0, (int) $value);
        }

        return 0;
    }
}
