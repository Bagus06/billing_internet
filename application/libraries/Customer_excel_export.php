<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_excel_export
{
    public function create(array $customers, $outputPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('File Excel sementara tidak dapat dibuat.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRelationships());
        $zip->addFromString('docProps/app.xml', $this->appProperties());
        $zip->addFromString('docProps/core.xml', $this->coreProperties());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($customers));
        $zip->close();
    }

    private function worksheet(array $customers)
    {
        $headers = ['No', 'ID Pelanggan', 'Nama', 'Telepon', 'NIK', 'Alamat', 'Latitude', 'Longitude', 'Paket', 'Biaya Bulanan', 'Tanggal PSB', 'Kelompok', 'Status Pelanggan', 'Status Pembayaran', 'Status Isolir', 'Tunggakan', 'Periode Tunggakan', 'Promotor', 'Catatan'];
        $rows = [$this->row(1, $headers, true)];
        $rowNumber = 2;

        foreach ($customers as $index => $customer) {
            $arrearsCount = (int) ($customer['arrears_count'] ?? 0);
            $values = [
                $index + 1,
                $customer['customer_code'] ?? '',
                $customer['name'] ?? '',
                $customer['phone'] ?? '',
                $customer['nik'] ?? '',
                $customer['address'] ?? '',
                $customer['latitude'] ?? '',
                $customer['longitude'] ?? '',
                $customer['package_name'] ?? '',
                (float) ($customer['price'] ?? 0),
                $customer['psb_date'] ?? '',
                $customer['group_name'] ?? '',
                $customer['customer_status'] ?? '',
                $customer['payment_status'] ?? '',
                !empty($customer['is_isolated']) ? 'ISOLIR' : 'NORMAL',
                $arrearsCount > 0 ? $arrearsCount . ' bulan - Rp ' . number_format((float) ($customer['arrears_amount'] ?? 0), 0, ',', '.') : 'Tidak ada',
                $customer['arrears_periods'] ?? '',
                $customer['promoter'] ?? '',
                $customer['notes'] ?? '',
            ];
            $rows[] = $this->row($rowNumber++, $values, false);
        }

        $lastRow = max(1, $rowNumber - 1);
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $this->columns() . '</cols><sheetData>' . implode('', $rows) . '</sheetData>'
            . '<autoFilter ref="A1:S' . $lastRow . '"/>'
            . '</worksheet>';
    }

    private function row($rowNumber, array $values, $header)
    {
        $cells = [];
        foreach ($values as $index => $value) {
            $reference = $this->columnName($index + 1) . $rowNumber;
            $style = $header ? 1 : (($index === 9) ? 2 : 0);
            if (!$header && ($index === 0 || $index === 9) && is_numeric($value)) {
                $cells[] = '<c r="' . $reference . '" s="' . $style . '"><v>' . $value . '</v></c>';
            } else {
                $cells[] = '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">' . $this->escape($value) . '</t></is></c>';
            }
        }
        return '<row r="' . $rowNumber . '"' . ($header ? ' ht="24" customHeight="1"' : '') . '>' . implode('', $cells) . '</row>';
    }

    private function columns()
    {
        $widths = [7, 18, 30, 18, 22, 38, 15, 15, 20, 17, 15, 15, 18, 20, 15, 23, 22, 20, 35];
        $xml = '';
        foreach ($widths as $index => $width) $xml .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        return $xml;
    }

    private function columnName($number)
    {
        $name = '';
        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = (int) floor($number / 26);
        }
        return $name;
    }

    private function escape($value)
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', (string) $value);
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>';
    }

    private function rootRelationships()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>';
    }

    private function workbook()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data Pelanggan" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private function workbookRelationships()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
    }

    private function styles()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><numFmts count="1"><numFmt numFmtId="164" formatCode="&quot;Rp&quot; #,##0"/></numFmts><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><color rgb="FFFFFFFF"/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1379B2"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf><xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"><alignment vertical="top"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }

    private function appProperties()
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>ISP Billing</Application></Properties>';
    }

    private function coreProperties()
    {
        $time = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:creator>ISP Billing</dc:creator><dc:title>Data Pelanggan</dc:title><dcterms:created xsi:type="dcterms:W3CDTF">' . $time . '</dcterms:created></cp:coreProperties>';
    }
}
