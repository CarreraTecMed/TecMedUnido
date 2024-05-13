<?php

namespace App\Http\Controllers;

use App\Exports\MoneyBoxExport;
use App\Http\Resources\MoneyBoxCollection;
use App\Models\MoneyBox;
use App\Models\Recharge;
use App\Models\Spent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Codedge\Fpdf\Fpdf\Fpdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use PDF;

class MoneyBoxController extends Controller
{
    public function getMoneyBox($id)
    {
        $cajaChica = MoneyBox::with('manager')->with('director')->find($id);
        $spents = Spent::orderBy('created_at', 'desc')->where('money_boxes_id', $cajaChica->id)->get();
        $recargas = Recharge::where('money_box_id', $cajaChica->id)->get();
        $montoInicial = $cajaChica->monto;
        $gastos = 1000 - $cajaChica->monto;

        foreach ($spents as $spent) {
            $gastos += $spent->gasto;
            $montoInicial -= $spent->gasto;
            if ($spent->ingreso === 'si') {
                $montoInicial += number_format($spent->gasto, 2);
                $gastos -= number_format($spent->gasto, 2);
            }
        }

        foreach ($recargas as $recarga) {
            $montoInicial += $recarga->montoRecarga;
            $gastos -= $recarga->montoRecarga;
        }
        $cajaChica->saldo = number_format($montoInicial, 2);
        $cajaChica->gasto = number_format($gastos, 2);
        $cajaChica->spents = $spents;
        return $cajaChica;
    }

    public function moneyBoxes()
    {
        $moneyBoxes = MoneyBox::all();
        return $moneyBoxes;
    }

    public function getMoneyBoxHistory($id)
    {
        $cajaChica = MoneyBox::find($id);
        $spents = Spent::orderBy('created_at', 'desc')->where('money_boxes_id', $cajaChica->id)->get();
        $recharges = Recharge::where('money_box_id',$id)->get();
        // $spents = Spent::all();
        $spentsRecharges = $spents->concat($recharges)->sortBy('created_at')->values();

        $montoInicial = $cajaChica->monto;
        $gastos = 1000 - $cajaChica->monto;

        foreach ($spentsRecharges as $spentRecharge) {
            if (isset($spentRecharge->estado)) { //recarga
                $montoInicial += floatval($spentRecharge->montoRecarga);
                $spentRecharge->saldo = $montoInicial;
                $gastos -= $spentRecharge->montoRecarga;
            } else { //gasto
                if ($spentRecharge->ingreso === 'si') {
                    $montoInicial += number_format(($spentRecharge->gasto), 2);
                    $gastos -= number_format(($spentRecharge->gasto), 2);
                }
                $montoInicial = $montoInicial - $spentRecharge->gasto;
                $spentRecharge->saldo = $montoInicial;
                $gastos += $spentRecharge->gasto;
            }
        }
        return [
            'gastos' => $spentsRecharges,
            'gastoAcumulado' => number_format($gastos, 2),
            'montoInicial' => $cajaChica->monto
        ];
    }

    public function createMoneyBox()
    {
        DB::beginTransaction();

        try {
            $currentYear = Carbon::now()->year;
            MoneyBox::create([
                'nombre' => 'CC. '.$currentYear,
                'monto' => 1000.00,
                'user_id' => '3',
                'director_teacher_id' => '1'
            ]);

            // $moneyBox->monto = $moneyBox->monto - $spent->gasto;

            DB::commit();
            return [
                "message" => "Caja chica creada correctamente",
            ];
        } catch (\Throwable $th) {
            DB::rollBack();
            return response([
                'errors' => ['Ocurrio algo inesperado con el servidor: ' . $th->getMessage()]
            ], 422);
        }
    }

    public function getMoneyBoxRecopilation($dateOne, $dateTwo, $id)
    {
        //Fechas
        $fechaInicial = Carbon::parse($dateOne);
        $fechaFinal = Carbon::parse($dateTwo);

        $cajaChica = MoneyBox::with('director')->with('manager')->find($id);
        $recharges = Recharge::where('money_box_id', $id)->get();

        $spentsModel = Spent::where('money_boxes_id', $id)->get();
        $spentsRecharges = $spentsModel->concat($recharges)->sortBy('created_at')->values();
        $montoInicial = $cajaChica->monto;
        $gastoInicial = 1000 - $cajaChica->monto;

        foreach ($spentsRecharges as $spentRecharge) {
            $fechaComparar = Carbon::parse(isset($spentRecharge->fechaCreacion) ? $spentRecharge->fechaCreacion : $spentRecharge->fechaRecarga);

            if ($fechaComparar->between($fechaInicial, $fechaFinal)) {
                break;
            }

            if (isset($spentRecharge->fechaCreacion)) { //gasto
                if ($spentRecharge->ingreso === 'si') {
                    $montoInicial += number_format(($spentRecharge->gasto), 2);
                    $gastoInicial -= number_format($spentRecharge->gasto, 2);
                }
                $montoInicial -= number_format($spentRecharge->gasto, 2);
                $gastoInicial += number_format($spentRecharge->gasto, 2);
            } else { //Reembolso
                $montoInicial += number_format($spentRecharge->montoRecarga);
                $gastoInicial -= number_format($spentRecharge->montoRecarga, 2);
            }
        }

        //HEADER

        $fechaInicial->setLocale('Es');
        $fechaFinal->setLocale('Es');
        $fechaMostrarOne = strtoupper($fechaInicial->isoFormat('D MMMM Y'));
        $fechaMostrarTwo = strtoupper($fechaFinal->isoFormat('D MMMM Y'));

        //Creadno el pdf 
        $pdf = new PDF_MC_Table();
        $pdf->AddPage('L', 'Legal');
        $pdf->SetFont('Arial', '', 14);
        $pdf->Image(public_path('logos/logoTecMed.png'), 319, 6, 20);
        $pdf->Image(public_path('logos/LogoUMSA.png'), 10, 6, 18, 30);
        $pdf->SetFont('Arial', 'B', 16,);
        $pdf->Cell(335, 8, iconv('UTF-8', 'windows-1252', 'UNIVERSIDAD MAYOR DE SAN ANDRÉS'), 0, 1, 'C');
        $pdf->Cell(335, 8, iconv('UTF-8', 'windows-1252', 'FACULTAD DE MEDICINA, ENFERMERÍA, NUTRICIÓN Y TECNOLOGÍA MÉDICA'), 0, 1, 'C');
        $pdf->Cell(335, 10, iconv('UTF-8', 'windows-1252', 'CARRERA DE TECNOLOGÍA MÉDICA'), 0, 1, 'C');
        $pdf->Cell(335, 5, '', 0, 1, 'C');
        $pdf->Cell(330, 10, iconv('UTF-8', 'windows-1252', 'DETALLE DE GASTOS DE CAJA CHICA CARRERA DE TECNOLOGÍA MÉDICA'), 0, 1, 'C');
        $pdf->Cell(330, 8, iconv('UTF-8', 'windows-1252', 'DEL ' . $fechaMostrarOne . ' AL ' . $fechaMostrarTwo), 0, 1, 'C');
        $pdf->Ln();
        // Definir encabezados de la tabla
        $header = array('Nro', 'Fecha', 'Factura', 'Detalle', 'Entregado a/Empresa', 'Ingreso', 'Gasto', 'Saldo');


        $widths = array(10, 40, 30, 150, 40, 20, 20, 20);
        $aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');

        // Agregar encabezados de tabla
        $pdf->SetFont('Arial', 'B', 10);
        for ($i = 0; $i < count($header); $i++) {
            $pdf->Cell($widths[$i], 10, $header[$i], 1, 0, 'C');
        }

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Ln();
        $pdf->Cell(270, 10, 'SALDO Y GASTO (ANTES DE FECHAS)', 1, 0, 'C');
        $pdf->Cell(20, 10, '', 1, 0, 'C');
        $pdf->Cell(20, 10, number_format($gastoInicial, 2) . ' Bs.', 1, 0, 'C');
        $pdf->Cell(20, 10, number_format($montoInicial, 2) . ' Bs.', 1, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Arial', '', 8);
        //Gastos que se realizaron entre fechas
        $spents = [];

        foreach ($spentsRecharges as $spentRecharge) {
            $fechaComparar = Carbon::parse(isset($spentRecharge->fechaCreacion) ? $spentRecharge->fechaCreacion : $spentRecharge->fechaRecarga);
            if ($fechaComparar->between($fechaInicial, $fechaFinal)) {
                $spents[] = $spentRecharge;
            }
        }
        //Datos

        $data = [];

        $pdf->SetWidths(array(10, 40, 30, 150, 40, 20, 20, 20));
        $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'));

        foreach ($spents as $spent) {
            $fechaCambiada = Carbon::createFromFormat('Y-m-d', isset($spent->fechaCreacion) ? $spent->fechaCreacion : $spent->fechaRecarga);

            if (isset($spent->fechaCreacion)) { //gasto 

                $montoInicial -= number_format($spent->gasto, 2);
                $gastoInicial += number_format($spent->gasto, 2);

                if ($spent->ingreso === 'si') {
                    $montoInicial += number_format($spent->gasto, 2);
                    $gastoInicial -= number_format($spent->gasto, 2);
                }

                $data[] = [
                    $spent->nro,
                    $fechaCambiada->format('d-m-Y'),
                    $spent->nroFactura !== '' && $spent->nroFactura !== null ? $spent->nroFactura : 'Sin factura',
                    iconv('UTF-8', 'windows-1252', $spent->descripcion),
                    iconv('UTF-8', 'windows-1252', $spent->interested),
                    $spent->ingreso === 'no' ? '' : number_format($spent->gasto, 2),
                    number_format($spent->gasto, 2) . ' Bs.',
                    number_format($montoInicial, 2) . ' Bs.'
                ];
            } else { //desembolso
                $data[] = [
                    '',
                    $fechaCambiada->format('d-m-Y'),
                    '',
                    'DESEMBOLSO CAJA CHICA:',
                    '',
                    $spent->montoRecarga . ' Bs.',
                    '',
                    number_format(($montoInicial + $spent->montoRecarga), 2) . ' Bs.'
                ];
                $gastoInicial -= number_format($spent->montoRecarga, 2);
                $montoInicial += number_format($spent->montoRecarga, 2);
            }
        };
        // for ($i = 0; $i < 22; $i++){ 
        //         $data[] = ['1', 'Dato ', 'Dato ', Str::random(rand(1, 250)), 'Dato 1', 'Dato 2','Dato','Dato'];
        //     };
        for ($i = 0; $i < count($data); $i++)
            $pdf->Row(array($data[$i][0], $data[$i][1], $data[$i][2], $data[$i][3], $data[$i][4], $data[$i][5], $data[$i][6], $data[$i][7]));

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->Cell(270, 10, 'SALDO Y GASTO (DESPUES DE FECHAS)', 1, 0, 'C');
        $pdf->Cell(20, 10, '', 1, 0, 'C');
        $pdf->Cell(20, 10, number_format($gastoInicial, 2) . ' Bs.', 1, 0, 'C');
        $pdf->Cell(20, 10, number_format($montoInicial, 2) . ' Bs.', 1, 0, 'C');

        $pdf->SetFont('Arial', '', 10);

        $encargado = $cajaChica->manager;
        $director = $cajaChica->director;

        if ($pdf->GetY() >= 200) {
            $this->AddPage('L', 'Legal');
            $pdf->SetXY(10, 30);
            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();
        }
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();
        //ENCARGADO
        $pdf->Cell(190, 5, '', 0, 0, 'C');
        $pdf->Ln();
        $pdf->Cell(190, 5, '', 0, 0, 'C');
        $pdf->Ln();
        $pdf->Cell(190, 5, '', 0, 0, 'C');
        $pdf->Ln();
        // $pdf->SetXY(55, 175);
        $pdf->Cell(190, 5, '........................................................................', 0, 0, 'C');
        $pdf->Cell(100, 5, '........................................................................', 0, 0, 'C');
        // $pdf->SetXY(60, 180);
        $pdf->Ln();
        $pdf->Cell(190, 5, iconv('UTF-8', 'windows-1252', $encargado->nombres . ' ' . $encargado->apellidoPaterno . ' ' . $encargado->apellidoMaterno), 0, 0, 'C');
        // $pdf->Cell(100, 5, 'Maria del Carmen Murillo de Espinoza');
        $pdf->Cell(100, 5, iconv('UTF-8', 'windows-1252', $director->gradoAcademico . ' ' . $director->nombreCompleto), 0, 0, 'C');
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(190, 5, 'Responsable de Caja Chica', 0, 0, 'C');

        $genero = substr($director->gradoAcademico, -1);
        $director = '';
        if ($genero === 'o') {
            $director = 'Director';
        } else {
            $director = 'Directora';
        }
        $pdf->Cell(100, 5, iconv('UTF-8', 'windows-1252', $director . ' Carrera Tecnología Médica'), 0, 0, 'C');

        // Salida del PDF
        $pdfContent = $pdf->Output('', 'S');
        return response()->json(['pdfContent' => base64_encode($pdfContent)]);
    }

    public function getMoneyBoxRecopilationExcel($dateOne, $dateTwo, $id)
    {
        //Fechas
        $fechaInicial = Carbon::parse($dateOne);
        $fechaFinal = Carbon::parse($dateTwo);

        $cajaChica = MoneyBox::with('director')->with('manager')->find($id);
        $recharges = Recharge::where('money_box_id', $id)->get();


        $spentsModel = Spent::where('money_boxes_id', $id)->get();
        $spentsRecharges = $spentsModel->concat($recharges)->sortBy('created_at')->values();
        $montoInicial = $cajaChica->monto;
        $gastoInicial = 1000 - $cajaChica->monto;

        foreach ($spentsRecharges as $spentRecharge) {
            $fechaComparar = Carbon::parse(isset($spentRecharge->fechaCreacion) ? $spentRecharge->fechaCreacion : $spentRecharge->fechaRecarga);

            if ($fechaComparar->between($fechaInicial, $fechaFinal)) {
                break;
            }

            if (isset($spentRecharge->fechaCreacion)) { //gasto
                if ($spentRecharge->ingreso === 'si') {
                    $montoInicial += number_format(($spentRecharge->gasto), 2);
                    $gastoInicial -= number_format($spentRecharge->gasto, 2);
                }
                $montoInicial -= number_format($spentRecharge->gasto, 2);
                $gastoInicial += number_format($spentRecharge->gasto, 2);
            } else { //Reembolso
                $montoInicial += number_format($spentRecharge->montoRecarga);
                $gastoInicial -= number_format($spentRecharge->montoRecarga, 2);
            }
        }

        $montoInicialPrimero = $montoInicial;
        $gastoInicialPrimero = $gastoInicial;

        $fechaInicial->setLocale('Es');
        $fechaFinal->setLocale('Es');
        $fechaMostrarOne = strtoupper($fechaInicial->isoFormat('D MMMM Y'));
        $fechaMostrarTwo = strtoupper($fechaFinal->isoFormat('D MMMM Y'));

        //Gastos que se realizaron entre fechas
        $spents = [];

        foreach ($spentsRecharges as $spentRecharge) {
            $fechaComparar = Carbon::parse(isset($spentRecharge->fechaCreacion) ? $spentRecharge->fechaCreacion : $spentRecharge->fechaRecarga);
            if ($fechaComparar->between($fechaInicial, $fechaFinal)) {
                $spents[] = $spentRecharge;
            }
        }
        //Datos

        $data = [];

        foreach ($spents as $spent) {
            $fechaCambiada = Carbon::createFromFormat('Y-m-d', isset($spent->fechaCreacion) ? $spent->fechaCreacion : $spent->fechaRecarga);

            if (isset($spent->fechaCreacion)) { //gasto 

                $montoInicial -= number_format($spent->gasto, 2);
                $gastoInicial += number_format($spent->gasto, 2);

                if ($spent->ingreso === 'si') {
                    $montoInicial += number_format($spent->gasto, 2);
                    $gastoInicial -= number_format($spent->gasto, 2);
                }

                $data[] = [
                    $spent->nro,
                    $fechaCambiada->format('d-m-Y'),
                    $spent->nroFactura !== '' && $spent->nroFactura !== null ? $spent->nroFactura : 'Sin factura',
                    iconv('UTF-8', 'windows-1252', $spent->descripcion),
                    iconv('UTF-8', 'windows-1252', $spent->interested),
                    $spent->ingreso === 'no' ? '' : number_format($spent->gasto, 2),
                    number_format($spent->gasto, 2) . ' Bs.',
                    number_format($montoInicial, 2) . ' Bs.'
                ];
            } else { //desembolso
                $data[] = [
                    '',
                    $fechaCambiada->format('d-m-Y'),
                    '',
                    'DESEMBOLSO CAJA CHICA:',
                    '',
                    $spent->montoRecarga . ' Bs.',
                    '',
                    number_format(($montoInicial + $spent->montoRecarga), 2) . ' Bs.'
                ];
                $gastoInicial -= number_format($spent->montoRecarga, 2);
                $montoInicial += number_format($spent->montoRecarga, 2);
            }
        };
        // for ($i = 0; $i < 22; $i++){ 
        //         $data[] = ['1', 'Dato ', 'Dato ', Str::random(rand(1, 250)), 'Dato 1', 'Dato 2','Dato','Dato'];
        //     };

        $encargado = $cajaChica->manager;
        $director = $cajaChica->director;
        $nombreDirector = $director->gradoAcademico . ' ' . $director->nombreCompleto;
        $genero = substr($director->gradoAcademico, -1);
        $director = '';
        if ($genero === 'o') {
            $director = 'Director';
        } else {
            $director = 'Directora';
        }

        // return (new FastExcel($data))->download('users.xlsx');


        array_unshift($data, ['', '', '', 'SALDO Y GASTO (ANTES DE FECHAS)', '', '', (string)number_format($gastoInicialPrimero, 2) . ' Bs.', (string)number_format($montoInicialPrimero, 2) . ' Bs.']);

        array_unshift($data, ['Nro', 'Fecha', 'Factura', 'Detalle', 'Entregado a/Empresa', 'Ingreso', 'Gasto', 'Saldo']);

        array_push($data, ['', '', '', 'SALDO Y GASTO (DESPUES DE FECHAS)', '', '', (string)number_format($gastoInicial, 2) . ' Bs.', (string)number_format($montoInicial, 2) . ' Bs.']);

        // Guardar el archivo en almacenamiento local
        // return Excel::download(new MoneyBoxExport($data), 'money_box.xlsx');
        $excelFile = Excel::raw(new MoneyBoxExport(
            $data,
            $encargado->nombres . ' ' . $encargado->apellidoPaterno . ' ' . $encargado->apellidoMaterno,
            $nombreDirector,
            $director . ' Carrera Tecnología Médica',
            'UNIVERSIDAD MAYOR DE SAN ANDRÉS FACULTAD DE MEDICINA, ENFERMERÍA, NUTRICIÓN Y TECNOLOGÍA MÉDICA' . "\n" . 'CARRERA DE TECNOLOGÍA MÉDICA' . "\n" . 'DETALLE DE GASTOS DE CAJA CHICA CARRERA DE TECNOLOGÍA MÉDICA' . "\n" . 'DEL ' . $fechaMostrarOne . ' AL ' . $fechaMostrarTwo
        ), ExcelWriter::XLSX);

        return response()->json(['fileContent' => base64_encode($excelFile)]);
    }

    public function editMoneyBox($id, Request $request)
    {
        $money_box = MoneyBox::find($id);

        $money_box->nombre = $request->nombre;
        $money_box->monto = $request->monto;
        $money_box->user_id = $request->user_id;

        $money_box->save();

        return [
            'message' => 'Caja chica editada correctamente'
        ];
    }

    public function selectManager($id, Request $request)
    {
        $money_box = MoneyBox::find($id);
        $money_box->user_id = $request->user_id;
        $money_box->save();
        return [
            'message' => 'Encargado seleccionado'
        ];
    }

    public function selectDirector($id, Request $request)
    {
        $money_box = MoneyBox::find($id);
        $money_box->director_teacher_id = $request->user_id;
        $money_box->save();
        return [
            'message' => 'Director seleccionado'
        ];
    }
}

class PDF_MC_Table extends FPDF
{
    protected $widths;
    protected $aligns;

    function SetWidths($w)
    {
        // Set the array of column widths
        $this->widths = $w;
    }

    function SetAligns($a)
    {
        // Set the array of column alignments
        $this->aligns = $a;
    }

    function Row($data)
    {
        // Calculate the height of the row
        $nb = 0;
        for ($i = 0; $i < count($data); $i++)
            $nb = max($nb, $this->NbLines($this->widths[$i], $data[$i]));
        $h = 5 * $nb;
        // Issue a page break first if needed
        $this->CheckPageBreak($h);
        // Draw the cells of the row
        for ($i = 0; $i < count($data); $i++) {
            $w = $this->widths[$i];
            $a = isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            // Save the current position
            $x = $this->GetX();
            $y = $this->GetY();
            // Draw the border
            $this->Rect($x, $y, $w, $h);
            // Print the text
            $this->MultiCell($w, 5, $data[$i], 0, $a);
            // Put the position to the right of the cell
            $this->SetXY($x + $w, $y);
        }
        // Go to the next line
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        // If the height h would cause an overflow, add a new page immediately
        if ($this->GetY() + $h > $this->PageBreakTrigger)
            // $this->AddPage($this->CurOrientation);
            $this->AddPage('L', 'Legal');
    }

    function NbLines($w, $txt)
    {
        // Compute the number of lines a MultiCell of width w will take
        if (!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = $this->CurrentFont['cw'];
        if ($w == 0)
            $w = $this->w - $this->rMargin - $this->x;
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] == "\n")
            $nb--;
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c == ' ')
                $sep = $i;
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j)
                        $i++;
                } else
                    $i = $sep + 1;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
            } else
                $i++;
        }
        return $nl;
    }
}
