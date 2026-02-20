<?php

namespace Tests\Unit;

use App\Support\BillingCycle;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class BillingCycleTest extends TestCase
{
    public function test_reference_date_advances_for_last_day_of_month(): void
    {
        $reference = BillingCycle::referenceDate(
            2026,
            2,
            Carbon::create(2026, 2, 28)->startOfDay()
        );

        $this->assertSame(3, $reference->month);
        $this->assertSame(2026, $reference->year);
    }

    public function test_reference_date_advances_for_last_business_day(): void
    {
        $reference = BillingCycle::referenceDate(
            2026,
            2,
            Carbon::create(2026, 2, 27)->startOfDay()
        );

        $this->assertSame(3, $reference->month);
        $this->assertSame(2026, $reference->year);
    }

    public function test_reference_date_keeps_same_month_for_regular_due_day(): void
    {
        $reference = BillingCycle::referenceDate(
            2026,
            2,
            Carbon::create(2026, 2, 10)->startOfDay()
        );

        $this->assertSame(2, $reference->month);
        $this->assertSame(2026, $reference->year);
    }
}
