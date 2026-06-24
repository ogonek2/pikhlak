<?php

namespace Tests\Unit;

use App\Services\Rental\RentBuyoutCalculator;
use PHPUnit\Framework\TestCase;

class RentBuyoutCalculatorTest extends TestCase
{
    public function test_example_from_spec_three_years(): void
    {
        $calc = new RentBuyoutCalculator();
        $r = $calc->calculate([
            'car_price' => 10000,
            'first_payment' => 2000,
            'term_years' => 3,
            'overpayment_rate' => 0.40,
            'weeks_per_year' => 52,
            'weeks_per_period' => 4,
            'currency' => 'USD',
        ]);

        $this->assertSame(8000.0, $r['remainder']);
        $this->assertSame(3200.0, $r['yearly_overpayment']);
        $this->assertSame(9600.0, $r['total_overpayment']);
        $this->assertSame(19600.0, $r['total_cost']);
        $this->assertSame(17600.0, $r['amount_to_finance']);
        $this->assertSame(156, $r['total_weeks']);
        $this->assertSame(39, $r['total_periods']);
        $this->assertEqualsWithDelta(451.28, $r['period_payment'], 0.01);
        $this->assertEqualsWithDelta(112.82, $r['weekly_payment'], 0.01);
    }

    public function test_five_years_uses_260_weeks(): void
    {
        $calc = new RentBuyoutCalculator();
        $r = $calc->calculate([
            'car_price' => 10000,
            'first_payment' => 2000,
            'term_years' => 5,
            'overpayment_rate' => 0.40,
            'weeks_per_year' => 52,
            'weeks_per_period' => 4,
            'currency' => 'USD',
        ]);

        $this->assertSame(260, $r['total_weeks']);
        $this->assertSame(65, $r['total_periods']);
        $this->assertSame(24000.0, $r['amount_to_finance']);
        $this->assertEqualsWithDelta(369.23, $r['period_payment'], 0.01);
    }
}
