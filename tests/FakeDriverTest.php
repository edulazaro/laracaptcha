<?php

namespace EduLazaro\Laracaptcha\Tests;

use EduLazaro\Laracaptcha\Facades\Captcha;
use Illuminate\Support\Facades\Http;

class FakeDriverTest extends TestCase
{
    public function test_fake_passes_by_default_and_records_attempts(): void
    {
        Http::fake();

        $fake = Captcha::fake();

        $result = Captcha::verify('a-token', '1.2.3.4');

        $this->assertTrue($result->passed());
        $this->assertSame([['token' => 'a-token', 'ip' => '1.2.3.4']], $fake->attempts());
        Http::assertNothingSent();
    }

    public function test_fake_can_fail(): void
    {
        $fake = Captcha::fake(success: false);

        $this->assertTrue(Captcha::verify('a-token')->failed());
        $this->assertCount(1, $fake->attempts());
    }

    public function test_fake_can_report_a_score(): void
    {
        Captcha::fake(score: 0.7);

        $this->assertSame(0.7, Captcha::verify('a-token')->score);
    }
}
