<?php

namespace Tests\Unit;

use App\Rules\Cpf;
use PHPUnit\Framework\TestCase;

class CpfRuleTest extends TestCase
{
    public function test_accepts_valid_cpf(): void
    {
        $failed = false;
        (new Cpf())->validate('document', '529.982.247-25', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertFalse($failed);
    }

    public function test_rejects_invalid_cpf(): void
    {
        $failed = false;
        (new Cpf())->validate('document', '111.111.111-11', function () use (&$failed): void {
            $failed = true;
        });

        $this->assertTrue($failed);
    }
}
