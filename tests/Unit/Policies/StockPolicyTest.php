<?php

namespace Tests\Unit\Policies;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Policies\StockPolicy;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_rules(): void
    {
        $policy = new StockPolicy;
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertTrue($policy->viewAny($owner));
        $this->assertTrue($policy->create($owner));
        $this->assertTrue($policy->view($owner, $stock));
        $this->assertTrue($policy->update($owner, $stock));
        $this->assertTrue($policy->delete($owner, $stock));

        $this->assertFalse($policy->view($other, $stock));
        $this->assertFalse($policy->update($other, $stock));
        $this->assertFalse($policy->delete($other, $stock));
    }

    public function test_admin_is_read_only(): void
    {
        $policy = new StockPolicy;
        $admin = User::factory()->admin()->create();
        $stock = Stock::factory()->create();

        $this->assertFalse($policy->viewAny($admin));
        $this->assertFalse($policy->create($admin));
        $this->assertTrue($policy->view($admin, $stock));
        $this->assertFalse($policy->update($admin, $stock));
        $this->assertFalse($policy->delete($admin, $stock));
    }

    public function test_policy_is_registered_with_gate(): void
    {
        $owner = User::factory()->create();
        $stock = Stock::factory()->for($owner)->create();

        $this->assertTrue($owner->can('update', $stock));
        $this->assertFalse(User::factory()->create()->can('update', $stock));
    }
}
