<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment; // Importante para centrar

class LeadsExport implements FromView, WithColumnWidths, WithStyles
{
    protected $leads;

    public function __construct($leads)
    {
        $this->leads = $leads;
    }

    public function view(): View
    {
        return view('exports.leads', [
            'leads' => $this->leads
        ]);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30, // Nombre
            'B' => 18, // Teléfono
            'C' => 35, // Correo
            'D' => 20, // Etapa
            'E' => 18, // Origen
            'F' => 45, // Mensaje
            'G' => 22, // Fecha
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // 1. Fila del Logo (Alto 90 para que respire)
            1 => ['row_height' => 90],

            // 2. Encabezados (Fila 4): Negritas
            4 => ['font' => ['bold' => true, 'size' => 12]],

            // 3. ESTILO GENERAL: Alinear verticalmente al centro TODO el documento
            'A:G' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER, // Centrado vertical (No pegado al piso)
                    'wrapText' => true, // Permitir que el texto baje si es muy largo
                ],
            ],

            // 4. ALINEACIONES ESPECÍFICAS (Para que se vea ordenado)
            'B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], // Teléfono centrado
            'D' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], // Etapa centrada
            'E' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], // Origen centrado
            'G' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]], // Fecha centrada
        ];
    }
}