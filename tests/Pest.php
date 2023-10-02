<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in('Feature');

expect()->extend('toBeOne', fn () => $this->toBe(1));
