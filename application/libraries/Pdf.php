<?php

require 'tools/tcpdf/tcpdf_include.php';

class Pdf {

    private $tcpdf; //pdf类库句柄
    private $fontName; //字体格式

    public function __construct() {

        //相关常量在/tcpdf/config下配置
        $this->tcpdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->fontName = $this->tcpdf->addTTFfont('tools/tcpdf/fonts/simfang.ttf', 'TrueTypeUnicode', '', 32);
        $this->tcpdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); //自动分页
        if (@file_exists('tools/tcpdf/lang/chi.php')) {
            require_once ( 'tools/tcpdf/lang/chi.php');
            $this->tcpdf->setLanguageArray($l);
        }
    }

    public function CreatePdf($filename, $content, $pwd = '', $type, $title) {
        ob_end_clean();
        $this->_setPdfInfo();
        //$time = date('Y-m-d H:i:s', time());
        //$this->setHeader("$title:  $time");
        //$this->setFooter();
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);
        if (!empty($pwd)) {
            $this->setPassword($pwd);
        }
        $this->_setBody();
        $this->setContent($content);

        $this->tcpdf->output($filename, $type);
    }

    //设置内容
    private function _setBody() {
        $this->tcpdf->addPage();
        $this->tcpdf->SetFont($this->fontName, '', 24);
    }

    //添加内容
    private function setContent($content) {
        $this->tcpdf->SetFont($this->fontName, '', 12);
        $this->tcpdf->writeHTML($content, 0, false, true, false, '');
    }

    /**
     *
     * 设置pdf文档页头
     * @param string $title
     */
    /*public function setHeader($title) {
        $this->tcpdf->SetTitle($title);
        $this->tcpdf->SetHeaderData('', 90, $title);
        // set header and footer fonts
        $this->tcpdf->setHeaderFont(Array($this->fontName, '', 12));
        $this->tcpdf->SetDefaultMonospacedFont($this->fontName);
        // set margins
        $this->tcpdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->tcpdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    }

    //
    public function setFooter() {
        $this->tcpdf->setFooterFont(Array($this->fontName, '', 10));
        $this->tcpdf->SetFooterMargin(PDF_MARGIN_FOOTER);
    }*/

    /**
     *
     * 设置文档密码
     *
     * @param string $password
     */
    public function setPassword($password) {
        $permissions = array('print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high');
        $this->tcpdf->SetProtection($permissions, $password);
    }

    /**
     * 设置pdf文档信息
     * Enter description here ...
     */
    private function _setPdfInfo() {
        $this->tcpdf->SetCreator(PDF_CREATOR);
        $this->tcpdf->SetAuthor('西有');
        $this->tcpdf->SetSubject('西有发票打印');
        $this->tcpdf->SetKeywords('TCPDF, PDF, 西有, shoplinq.cn');
    }

}
