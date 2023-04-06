<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;

class CreateSuperAdminSeeder extends Seeder
{
    public function run()
    {
        $administrator = new \App\Model\Admin();
        $administrator->name = 'wxl';
        $administrator->email = '2681977867@qq.com';
        $administrator->password = password_hash('123456', PASSWORD_DEFAULT);
        $administrator->save();
    }
}
