<?php
namespace FantacalcioAnalyzer\Utils;

/**
 * Generatore XLSX minimale (OOXML zippato) – nessuna dipendenza esterna.
 * Richiede estensione PHP ZipArchive attiva.
 */
class SimpleXLSXGen {
    private array $data = [];

    public static function fromArray(array $rows): self {
        $i = new self();
        $i->data = $rows;
        return $i;
    }

    public function toString(): string {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive non disponibile: impossibile creare XLSX.');
        }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        if ($zip->open($tmp, \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossibile creare ZIP XLSX');
        }
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->relsRoot());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->relsWorkbook());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet1());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->close();
        $bin = file_get_contents($tmp);
        @unlink($tmp);
        return $bin === false ? '' : $bin;
    }

    private function contentTypes(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' .
          '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' .
          '<Default Extension="xml" ContentType="application/xml"/>' .
          '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' .
          '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' .
          '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' .
        '</Types>';
    }
    private function relsRoot(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
          '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' .
        '</Relationships>';
    }
    private function relsWorkbook(): string {
        return '<?xml version="1.0" encoding="UTF-8"?>' .
        '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
          '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' .
          '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' .
        '</Relationships>';
    }
    private function workbook(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" ' .
                  'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">' .
          '<sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets>' .
        '</workbook>';
    }
    private static function colLetter(int $i): string {
        $i++; $s = '';
        while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
        return $s;
    }
    private function sheet1(): string {
        $rowsXml = '';
        foreach ($this->data as $rIdx => $row) {
            $r = $rIdx + 1;
            $cells = '';
            foreach (array_values($row) as $cIdx => $val) {
                $ref = self::colLetter($cIdx) . $r;
                if (is_numeric($val)) {
                    $cells .= '<c r="'.$ref.'"><v>'.(0+$val).'</v></c>';
                } else {
                    $v = htmlspecialchars((string)$val, ENT_XML1|ENT_QUOTES, 'UTF-8');
                    $cells .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$v.'</t></is></c>';
                }
            }
            $rowsXml .= '<row r="'.$r.'">'.$cells.'</row>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
               '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
                 '<sheetData>'.$rowsXml.'</sheetData>' .
               '</worksheet>';
    }
    private function styles(): string {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' .
        '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' .
          '<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>' .
          '<fills count="1"><fill><patternFill patternType="none"/></fill></fills>' .
          '<borders count="1"><border/></borders>' .
          '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' .
          '<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>' .
        '</styleSheet>';
    }
}
