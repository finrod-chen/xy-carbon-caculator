<?php

namespace Tests\Unit;

use App\Support\SeparationOfDuties;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SeparationOfDutiesTest extends TestCase
{
    public function test_reviewer_cannot_review_their_own_entry(): void
    {
        $this->assertFalse(SeparationOfDuties::canReview(7, 7));
    }

    public function test_another_reviewer_can_review(): void
    {
        $this->assertTrue(SeparationOfDuties::canReview(7, 8));
    }

    public function test_unknown_entrant_is_rejected(): void
    {
        $this->assertFalse(SeparationOfDuties::canReview(null, 8));
    }

    public function test_ensure_aborts_with_403(): void
    {
        $this->expectException(HttpException::class);

        SeparationOfDuties::ensure(7, 7);
    }
}
