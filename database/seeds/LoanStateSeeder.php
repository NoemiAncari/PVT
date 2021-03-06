<?php

use Illuminate\Database\Seeder;
use App\Module;
use App\LoanState;

class LoanStateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $states = [
            ['name' => 'En Proceso', 'description' => 'Prestamo en tramite' ],
            ['name' => 'Anulado', 'description' => 'Tramite del prestamo Anulado' ],
            ['name' => 'Desembolsado', 'description' => 'Dinero Desembolsado' ],
            ['name' => 'Liquidado', 'description' => 'Pago de la deuda en su totalidad' ],
            ['name' => 'Pendiente de Pago', 'description' => 'Registro de pago pendiente' ],
            ['name' => 'Pagado', 'description' => 'Registro de pago finalizado' ],
            ['name' => 'Pendiente de ajuste', 'description' => 'Registro de Pendiente de ajuste' ],
        ];
        foreach ($states as $state) {
            LoanState::firstOrCreate($state);
        }
    }
}
