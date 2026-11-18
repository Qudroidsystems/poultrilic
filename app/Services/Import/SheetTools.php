<?php

namespace App\Services\Import;

use Carbon\Carbon;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/** Small helpers for reading and writing the import templates and record exports. */
class SheetTools
{
    public static function norm(string $s): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii($s)));
    }

    public static function value(Worksheet $sheet, int $col, int $row): mixed
    {
        $cell = $sheet->getCell([$col, $row]);
        $v = $cell->getValue();
        if (is_string($v) && str_starts_with($v, '=')) {
            try {
                $v = $cell->getCalculatedValue();
            } catch (\Throwable) {
                $v = $cell->getOldCalculatedValue();
            }
        }
        if ($v instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
            $v = $v->getPlainText();
        }
        if (is_string($v)) {
            $v = trim($v);
            if ($v === '' || $v === '-' || $v === '–') {
                return null;
            }
        }

        return $v;
    }

    public static function date(mixed $v): ?Carbon
    {
        if ($v === null || $v === '') {
            return null;
        }
        try {
            if ($v instanceof \DateTimeInterface) {
                return Carbon::instance($v)->startOfDay();
            }
            if (is_numeric($v) && (float) $v > 20000 && (float) $v < 80000) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $v))->startOfDay();
            }
            if (is_string($v)) {
                $d = explode(' ', str_replace(['.', '-'], '/', $v))[0];
                if (preg_match('#^\d{1,2}/\d{1,2}/\d{2,4}$#', $d)) {
                    return Carbon::createFromFormat(strlen($d) > 8 ? 'd/m/Y' : 'd/m/y', $d)->startOfDay();
                }

                return Carbon::parse($v)->startOfDay();
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * Build a workbook: a data sheet with styled headers (and optional rows), and a "How to fill" sheet.
     *
     * @param  array<int, array{0: string, 1: string}>  $headers  [header, note]
     */
    public static function workbook(string $title, array $headers, array $rows = [], array $help = [], array $lists = []): Spreadsheet
    {
        $book = new Spreadsheet();
        $sheet = $book->getActiveSheet();
        $sheet->setTitle(Str::limit($title, 31, ''));

        foreach ($headers as $i => [$h, $note]) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}1", $h);
            if ($note) {
                $sheet->getComment("{$col}1")->getText()->createTextRun($note);
            }
            $sheet->getColumnDimension($col)->setWidth(max(12, min(34, strlen($h) + 4)));
        }
        $last = Coordinate::stringFromColumnIndex(count($headers));
        $sheet->getStyle("A1:{$last}1")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$last}1")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('405189');
        $sheet->getStyle("A1:{$last}1")->getAlignment()->setWrapText(true)->setVertical('center');
        $sheet->getRowDimension(1)->setRowHeight(32);
        $sheet->freezePane('A2');

        $r = 2;
        foreach ($rows as $row) {
            foreach (array_values($row) as $i => $v) {
                $col = $i + 1;
                if ($v instanceof \DateTimeInterface) {
                    $sheet->setCellValue([$col, $r], ExcelDate::PHPToExcel($v));
                    $sheet->getStyle([$col, $r])->getNumberFormat()->setFormatCode('dd mmm yyyy');
                } else {
                    $sheet->setCellValue([$col, $r], $v);
                }
            }
            $r++;
        }
        // Date column formatting for rows typed in later.
        foreach ($headers as $i => [$h]) {
            if (self::norm($h) === 'date') {
                $col = Coordinate::stringFromColumnIndex($i + 1);
                $sheet->getStyle("{$col}2:{$col}2000")->getNumberFormat()->setFormatCode('dd mmm yyyy');
            }
        }
        $sheet->setAutoFilter("A1:{$last}" . max(1, $r - 1));

        // Drop-down lists (e.g. flock names) on the data sheet.
        if ($lists) {
            $listSheet = $book->createSheet();
            $listSheet->setTitle('Lists');
            $c = 1;
            foreach ($lists as $header => $values) {
                $listSheet->setCellValue([$c, 1], $header);
                foreach (array_values($values) as $j => $v) {
                    $listSheet->setCellValue([$c, $j + 2], $v);
                }
                $colIdx = array_search($header, array_column($headers, 0), true);
                if ($colIdx !== false && $values) {
                    $L = Coordinate::stringFromColumnIndex($c);
                    $D = Coordinate::stringFromColumnIndex($colIdx + 1);
                    $validation = $sheet->getCell("{$D}2")->getDataValidation();
                    $validation->setType('list')->setAllowBlank(true)->setShowDropDown(true)
                        ->setFormula1("Lists!\${$L}\$2:\${$L}\$" . (count($values) + 1));
                    $sheet->setDataValidation("{$D}2:{$D}2000", $validation);
                }
                $c++;
            }
        }

        if ($help) {
            $hs = $book->createSheet();
            $hs->setTitle('How to fill');
            $hs->getColumnDimension('A')->setWidth(110);
            foreach ($help as $i => $line) {
                $hs->setCellValue('A' . ($i + 1), $line);
            }
            $hs->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        }
        $book->setActiveSheetIndex(0);

        return $book;
    }

    public static function download(Spreadsheet $book, string $filename)
    {
        return response()->streamDownload(function () use ($book) {
            (new Xlsx($book))->save('php://output');
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }
}
