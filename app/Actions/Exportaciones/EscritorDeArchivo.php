<?php

namespace App\Actions\Exportaciones;

use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Bom;
use League\Csv\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer as EscritorXlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Entrega un Exportador en el formato pedido. CSV, TXT y XLSX se escriben
 * por streaming (no cargan las filas en memoria); el PDF sí necesita todas
 * las filas a la vez, por eso tiene un tope.
 */
class EscritorDeArchivo
{
    public const FORMATOS = ['csv', 'txt', 'xlsx', 'pdf'];

    /** Máximo de filas que admite el PDF. */
    public const MAX_FILAS_PDF = 2000;

    /**
     * @throws ExportacionDemasiadoGrande cuando el PDF supera el tope.
     */
    public function descargar(Exportador $exportador, string $formato, string $nombreBase): Response
    {
        $nombre = "{$nombreBase}-".now()->format('Y-m-d').".{$formato}";

        return match ($formato) {
            'csv' => $this->delimitado($exportador, $nombre, ';', 'text/csv', conBom: true),
            'txt' => $this->delimitado($exportador, $nombre, "\t", 'text/plain', conBom: true),
            'xlsx' => $this->xlsx($exportador, $nombre),
            'pdf' => $this->pdf($exportador, $nombre),
        };
    }

    /**
     * CSV y TXT: texto con un delimitador. El BOM hace que Excel respete las tildes.
     */
    public function delimitado(Exportador $exportador, string $nombre, string $delimitador, string $tipo, bool $conBom): StreamedResponse
    {
        return response()->streamDownload(function () use ($exportador, $delimitador, $conBom): void {
            $escritor = Writer::createFromStream(fopen('php://output', 'w'));
            $escritor->setDelimiter($delimitador);

            if ($conBom) {
                $escritor->setOutputBOM(Bom::Utf8);
            }

            $escritor->insertOne($exportador->encabezados());

            foreach ($exportador->filas() as $fila) {
                $escritor->insertOne($fila);
            }
        }, $nombre, ['Content-Type' => "{$tipo}; charset=UTF-8"]);
    }

    private function xlsx(Exportador $exportador, string $nombre): StreamedResponse
    {
        return response()->streamDownload(function () use ($exportador): void {
            $escritor = new EscritorXlsx;
            $escritor->openToFile('php://output');

            $escritor->addRow(Row::fromValuesWithStyle($exportador->encabezados(), (new Style)->withFontBold(true)));

            foreach ($exportador->filas() as $fila) {
                $escritor->addRow(Row::fromValues($fila));
            }

            $escritor->close();
        }, $nombre, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function pdf(Exportador $exportador, string $nombre): Response
    {
        $filas = [];

        foreach ($exportador->filas() as $fila) {
            if (count($filas) >= self::MAX_FILAS_PDF) {
                throw new ExportacionDemasiadoGrande(self::MAX_FILAS_PDF);
            }

            $filas[] = $fila;
        }

        return Pdf::loadView('exportaciones.tabla', [
            'titulo' => $exportador->titulo(),
            'encabezados' => $exportador->encabezados(),
            'filas' => $filas,
        ])->setPaper('a4', count($exportador->encabezados()) > 6 ? 'landscape' : 'portrait')->download($nombre);
    }
}
