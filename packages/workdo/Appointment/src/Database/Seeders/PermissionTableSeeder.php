<?php

namespace Workdo\Appointment\Database\Seeders;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        Artisan::call('cache:clear');

        $permission = [
            ['name' => 'manage-appointments', 'module' => 'appointments', 'label' => 'Manage Appointments'],
            ['name' => 'manage-own-appointments', 'module' => 'appointments', 'label' => 'Manage Own Appointments'],
            ['name' => 'manage-any-appointments', 'module' => 'appointments', 'label' => 'Manage All Appointments'],
            ['name' => 'view-appointments', 'module' => 'appointments', 'label' => 'View Appointments'],
            ['name' => 'create-appointments', 'module' => 'appointments', 'label' => 'Create Appointments'],
            ['name' => 'edit-appointments', 'module' => 'appointments', 'label' => 'Edit Appointments'],
            ['name' => 'delete-appointments', 'module' => 'appointments', 'label' => 'Delete Appointments'],

            ['name' => 'manage-appointment-types', 'module' => 'appointment-types', 'label' => 'Manage Appointment Types'],
            ['name' => 'manage-own-appointment-types', 'module' => 'appointment-types', 'label' => 'Manage Own Appointment Types'],
            ['name' => 'manage-any-appointment-types', 'module' => 'appointment-types', 'label' => 'Manage All Appointment Types'],
            ['name' => 'create-appointment-types', 'module' => 'appointment-types', 'label' => 'Create Appointment Types'],
            ['name' => 'edit-appointment-types', 'module' => 'appointment-types', 'label' => 'Edit Appointment Types'],
            ['name' => 'delete-appointment-types', 'module' => 'appointment-types', 'label' => 'Delete Appointment Types'],

            ['name' => 'manage-appointment-availability', 'module' => 'appointment-availability', 'label' => 'Manage Availability'],
            ['name' => 'create-appointment-availability', 'module' => 'appointment-availability', 'label' => 'Create Availability'],
            ['name' => 'edit-appointment-availability', 'module' => 'appointment-availability', 'label' => 'Edit Availability'],
            ['name' => 'delete-appointment-availability', 'module' => 'appointment-availability', 'label' => 'Delete Availability'],
        ];

        $company_role = Role::where('name', 'company')->first();

        foreach ($permission as $perm) {
            $permission_obj = Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                [
                    'module' => $perm['module'],
                    'label' => $perm['label'],
                    'add_on' => 'Appointment',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            );

            if ($company_role && !$company_role->hasPermissionTo($permission_obj)) {
                $company_role->givePermissionTo($permission_obj);
            }
        }
    }
}
