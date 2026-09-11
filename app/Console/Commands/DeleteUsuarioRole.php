<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:delete-usuario-role')]
#[Description('Command description')]
class DeleteUsuarioRole extends Command
{
    /**
     * Execute the console command.
     */
public function handle()
{
    \App\Models\Role::where('nombre', 'Usuario')->delete();
    $this->info('El rol Usuario ha sido eliminado correctamente.');
}
}
