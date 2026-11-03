<?php

namespace App\Embeddings;

class Vectors
{
    /**
     * @param  list<float>  $v
     * @return list<float>
     */
    public static function normalise(array $v): array
    {
        $norm = sqrt(array_sum(array_map(fn ($x) => $x * $x, $v)));

        return $norm > 0 ? array_map(fn ($x) => $x / $norm, $v) : $v;
    }

    /**
     * Running mean of unit vectors, renormalised.
     *
     * @param  list<float>  $mean
     * @param  list<float>  $next
     * @return list<float>
     */
    public static function addToMean(array $mean, int $count, array $next): array
    {
        $out = [];
        foreach ($mean as $i => $value) {
            $out[] = ($value * $count + $next[$i]) / ($count + 1);
        }

        return self::normalise($out);
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    public static function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        foreach ($a as $i => $value) {
            $dot += $value * $b[$i];
        }

        return $dot;
    }
}
