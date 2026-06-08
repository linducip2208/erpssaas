<?php

namespace Workdo\ProjectTemplate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class ProjectTemplateDatabaseSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();

        $this->call(PermissionTableSeeder::class);
    }
}
