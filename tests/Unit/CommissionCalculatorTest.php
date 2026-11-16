<?php

namespace Tests\Unit;

use App\Services\CommissionCalculator;
use Tests\TestCase;

class CommissionCalculatorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['porrop.commission' => ['rate' => 10, 'min' => 100, 'max' => 5000]]);
    }

    public function test_ten_percent_in_the_middle(): void
    {
        $r = (new CommissionCalculator)->forSubtotal(12000);
        $this->assertSame(1200.0, $r['commission']);
        $this->assertSame(10800.0, $r['payout']);
    }

    public function test_minimum_applies_to_small_orders(): void
    {
        $this->assertSame(100.0, (new CommissionCalculator)->forSubtotal(500)['commission']);
    }

    public function test_cap_applies_to_big_orders(): void
    {
        $r = (new CommissionCalculator)->forSubtotal(200000);
        $this->assertSame(5000.0, $r['commission']);
        $this->assertSame(195000.0, $r['payout']);
    }

    public function test_fee_never_exceeds_subtotal(): void
    {
        $r = (new CommissionCalculator)->forSubtotal(60);
        $this->assertSame(60.0, $r['commission']);
        $this->assertSame(0.0, $r['payout']);
    }

    public function test_zero_subtotal(): void
    {
        $this->assertSame(0.0, (new CommissionCalculator)->forSubtotal(0)['commission']);
    }
}
