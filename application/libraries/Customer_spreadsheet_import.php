<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Customer_spreadsheet_import
{
    private $ci;
    private $sheetId = '1nZMp03Vp916BZB0KjQIVM3t0cM3-j1Fikr3h9q5tgIo';

    public function __construct()
    {
        $this->ci =& get_instance();
    }

    public function run()
    {
        $customers = $this->csv('DATA_PELANGGAN');
        $payments = $this->csv('PEMBAYARAN');
        $result = ['customers_created'=>0,'customers_updated'=>0,'payments_created'=>0,'payments_skipped'=>0,'errors'=>[]];
        $this->ci->db->trans_begin();
        try {
            foreach ($customers as $row) $this->importCustomer($row, $result);
            foreach ($payments as $row) $this->importPayment($row, $result);
            if ($this->ci->db->trans_status() === false) throw new RuntimeException('Transaksi database gagal.');
            $this->ci->db->trans_commit();
        } catch (Throwable $e) {
            $this->ci->db->trans_rollback(); throw $e;
        }
        return $result;
    }

    private function importCustomer(array $r, array &$result)
    {
        $code=trim($r['ID']??''); $nik=preg_replace('/\D+/','',$r['NIK KTP']??'');
        if ($code==='' || $nik==='') { $result['errors'][]='Pelanggan tanpa ID/NIK dilewati.'; return; }
        $packageName=trim($r['Paket']??'');
        $package=$this->ci->db->where('package_name',$packageName)->get('internet_packages')->row_array();
        $data=['customer_code'=>$code,'name'=>trim($r['NAMA']??''),'phone'=>trim($r['Telephone']??''),'nik'=>$nik,
            'ktp_photo'=>trim($r['Foto KTP']??''),'address'=>trim($r['Alamat']??''),'package_id'=>$package?(int)$package['id']:null,
            'package_name'=>$package?$package['package_name']:$packageName,'price'=>$package?(float)$package['price']:$this->price($r['Harga']??0),
            'psb_date'=>$this->date($r['Tanggal PSB']??''),'group_name'=>trim($r['Kelompok']??''),
            'customer_status'=>strtoupper(trim($r['Status Pelanggan']??'ACTIVE'))==='NONACTIVE'?'NONACTIVE':'ACTIVE',
            'promoter'=>trim($r['Promotor']??''),'notes'=>trim($r['Keterangan']??''),'updated_at'=>date('Y-m-d H:i:s')];
        $this->ci->db->group_start()->where('customer_code',$code)->or_where('nik',$nik)->group_end();
        $existing=$this->ci->db->get('customers')->row_array();
        if ($existing) { $this->ci->db->where('id',$existing['id'])->update('customers',$data); $result['customers_updated']++; }
        else { $data['created_at']=$data['updated_at']; $this->ci->db->insert('customers',$data); $result['customers_created']++; }
    }

    private function importPayment(array $r, array &$result)
    {
        $code=trim($r['ID Pelanggan']??''); if ($code==='') return;
        $customer=$this->ci->db->where('customer_code',$code)->get('customers')->row_array();
        if (!$customer) { $result['errors'][]='Pembayaran '.$code.' dilewati: pelanggan tidak ditemukan.'; return; }
        $month=max(1,min(12,(int)($r['Bulan Tagihan']??0))); $year=(int)($r['Tahun']??0);
        $paymentDate=$this->date($r['Tanggal Bayar']??''); $type=strtoupper(trim($r['Pembayaran']??'BULANAN'));
        if (!in_array($type,['PSB','BULANAN'],true)) $type='BULANAN';
        $duplicate=$this->ci->db->where(['customer_code'=>$code,'bill_month'=>$month,'bill_year'=>$year,
            'payment_type'=>$type,'payment_date'=>$paymentDate])->count_all_results('customer_payments');
        if ($duplicate) { $result['payments_skipped']++; return; }
        $this->ci->db->insert('customer_payments',['customer_id'=>(int)$customer['id'],'customer_code'=>$code,
            'customer_name'=>trim($r['Nama']??$customer['name']),'bill_month'=>$month,'bill_year'=>$year,
            'package_name'=>trim($r['Paket']??$customer['package_name']),'price'=>$this->price($r['Harga']??$customer['price']),
            'group_name'=>trim($r['Kelompok']??$customer['group_name']),'payment_type'=>$type,'payment_date'=>$paymentDate,
            'payment_method'=>strtoupper(trim($r['Metode Bayar']??'CASH')),'notes'=>trim($r['Keterangan']??''),
            'input_date'=>$this->date($r['Tanggal Input']??$paymentDate),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
        $result['payments_created']++;
    }

    private function csv($sheet)
    {
        $url='https://docs.google.com/spreadsheets/d/'.$this->sheetId.'/gviz/tq?tqx=out:csv&sheet='.rawurlencode($sheet);
        $context=stream_context_create(['http'=>['timeout'=>30,'user_agent'=>'BillingInternet/1.0']]);
        $content=@file_get_contents($url,false,$context);
        if ($content===false) throw new RuntimeException('Gagal mengambil sheet '.$sheet.'. Pastikan sheet dapat diakses publik dan server memiliki akses internet.');
        $lines=preg_split('/\r\n|\n|\r/',$content); $headers=str_getcsv(array_shift($lines)); $rows=[];
        foreach($lines as $line){if(trim($line)==='')continue;$values=str_getcsv($line);$row=[];foreach($headers as $i=>$header){$header=trim($header);if($header!=='')$row[$header]=$values[$i]??'';}if(array_filter($row,function($v){return trim((string)$v)!=='';}))$rows[]=$row;}
        return $rows;
    }

    private function price($value){$value=str_replace(['.',' '],'',(string)$value);$value=str_replace(',','.',$value);return is_numeric($value)?(float)$value:0;}
    private function date($value){$value=trim((string)$value);if($value==='')return null;$id=['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];$en=['January','February','March','April','May','June','July','August','September','October','November','December'];$time=strtotime(str_ireplace($id,$en,$value));return $time?date('Y-m-d',$time):null;}
}
