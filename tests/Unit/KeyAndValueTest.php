<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Domain\KeyStore\Exceptions\InvalidKey;
use App\Domain\KeyStore\Exceptions\ReservedKey;
use App\Domain\KeyStore\Exceptions\ValueTooDeep;
use App\Domain\KeyStore\Key;
use App\Domain\KeyStore\Value;
use PHPUnit\Framework\TestCase;

final class KeyAndValueTest extends TestCase
{
    public function test_accepts_alphanumeric_underscore_and_dash(): void
    {
        $this->assertSame('my-key', Key::parse('my-key')->value);
        $this->assertSame('user_id', Key::parse('user_id')->value);
        $this->assertSame('MyKey', Key::parse('MyKey')->value);
    }

    public function test_mykey_and_my_key_are_different(): void
    {
        $this->assertNotSame(Key::parse('mykey')->value, Key::parse('MyKey')->value);
    }

    public function test_rejects_reserved_list_path(): void
    {
        $this->expectException(ReservedKey::class);
        Key::parse('get_all_records');
    }

    public function test_rejects_leading_dash_and_dot(): void
    {
        $this->expectException(InvalidKey::class);
        Key::parse('-hidden');
    }

    public function test_allows_scalars_and_depth_two(): void
    {
        $this->assertSame(0, Value::depth('hello'));
        $this->assertSame(0, Value::depth(null));
        $this->assertSame(1, Value::depth((object) ['a' => 1]));
        $this->assertSame(2, Value::depth((object) ['a' => (object) ['b' => 1]]));
        Value::fromJson((object) ['a' => (object) ['b' => 1]]);
    }

    public function test_rejects_depth_three_objects(): void
    {
        $this->expectException(ValueTooDeep::class);
        Value::fromJson((object) ['a' => (object) ['b' => (object) ['c' => 1]]]);
    }

    public function test_arrays_count_as_a_level(): void
    {
        $this->expectException(ValueTooDeep::class);
        Value::fromJson((object) ['a' => [1, (object) ['b' => 1]]]);
    }
}
