<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedTenant('Springfield Primary School', 'springfield.demo');
        $this->seedTenant('Riverside Secondary College', 'riverside.demo');
    }

    private function seedTenant(string $name, string $emailDomain): void
    {
        $tenant = Tenant::create(['name' => $name]);

        tenancy()->initialize($tenant);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UserSeeder::class, false, ['emailDomain' => $emailDomain]);
        $this->call(YearLevelSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(ClassSeeder::class, false, ['emailDomain' => $emailDomain]);

        tenancy()->end();
    }
}
