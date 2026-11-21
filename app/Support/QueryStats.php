<?php

namespace App\Support;

class QueryStats
{
    public int $count = 0;
    public float $ms = 0.0;

    public function record(float $ms): void
    {
        $this->count++;
        $this->ms += $ms;
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->ms = 0.0;
    }
}