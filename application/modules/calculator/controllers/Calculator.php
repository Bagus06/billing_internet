<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Calculator extends CI_Controller
{
    public function index()
    {
        $data = [
            'title' => 'Calculator Redaman - ISP BATARA NET',
            'body_class' => 'calculator-page',
            'splitter_loss' => $this->splitterLoss(),
        ];

        $this->load->view('../../views/layout/header', $data);
        $this->load->view('index', $data);
        $this->load->view('../../views/layout/footer', [
            'module_jsload' => APPPATH . 'modules/calculator/jsload.php',
        ]);
    }

    private function splitterLoss()
    {
        return [
            'PLC' => [
                '1:2' => ['P1' => 3.8, 'P2' => 3.8],
                '1:4' => ['P1' => 7.2, 'P2' => 7.2, 'P3' => 7.2, 'P4' => 7.2],
                '1:8' => ['P1' => 10.5, 'P2' => 10.5, 'P3' => 10.5, 'P4' => 10.5, 'P5' => 10.5, 'P6' => 10.5, 'P7' => 10.5, 'P8' => 10.5],
                '1:16' => array_fill_keys(array_map(function ($i) {
                    return 'P' . $i;
                }, range(1, 16)), 13.8),
            ],
            'FBT Unequal' => [
                '99:1'  => ['99%' => 0.2, '1%' => 20.5],
                '95:5'  => ['95%' => 0.3, '5%' => 13.8],
                '90:10' => ['90%' => 0.5, '10%' => 10.5],
                '80:20' => ['80%' => 1.0, '20%' => 7.4],
                '70:30' => ['70%' => 1.6, '30%' => 5.8],
                '60:40' => ['60%' => 2.3, '40%' => 4.4],
                '50:50' => ['50% A' => 3.6, '50% B' => 3.6],
            ],
        ];
    }
}
