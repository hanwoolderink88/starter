<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Infrastructure\Activity\Enums\ActivityEvent;
use App\Infrastructure\Activity\Enums\ActivitySource;
use App\Infrastructure\Activity\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_type' => (new User)->getMorphClass(),
            'subject_id' => User::factory(),
            'causer_id' => null,
            'causer_name' => fake()->name(),
            'impersonator_id' => null,
            'impersonator_name' => null,
            'source' => ActivitySource::Web,
            'event' => ActivityEvent::Updated,
            'changes' => [],
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function about(Model $subject): static
    {
        return $this->state([
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
        ]);
    }

    public function causedBy(User $causer): static
    {
        return $this->state([
            'causer_id' => $causer->id,
            'causer_name' => $causer->name,
        ]);
    }
}
