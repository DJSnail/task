<?php

/************************************************************
 ** @Description: 文献信息
 ** @Author: haodaquan
 ** @Date: 2017-12-04 12:05:24
 ** @Last Modified by:   haodaquan
 ** @Last Modified time: 2017-12-04 12:05:24
 *************************************************************/
class Literature_model extends MY_Model
{
    protected $_table;

    public function __construct()
    {
        parent::__construct();
        $this->_table = 'uc_literature';
    }

    /**
     * [getReportInfo 获取文献]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getLiteratureInfo($id)
    {
        $sql = 'SELECT
				p.*,
				m.nickname,
				mc.username as main_nickname
			FROM
				uc_literature AS p
			JOIN uc_member AS m ON p.uid=m.id
			JOIN uc_connection AS mc ON p.main_conn=mc.id
			WHERE
				p.id =' . $id;

        $query = $this->db->query($sql);

        return $query->result_array();
    }

    public function relay($literature_id)
    {
        $sql = 'SELECT
				p.*,
				mc.*
			FROM
				uc_literature AS p
			JOIN uc_connection AS mc ON p.main_conn=mc.id
			WHERE
				p.id =' . $literature_id;
        $query = $this->db->query($sql);
        $info = $query->row_array();
        if(!$info){
            return false;
        }
        if($info['relay_status'] == 1){
            return '请勿重复转发';
        }
        //用户信息
        $username = $info['username'];
        $UserTel = $info['phone'];
        $UserAddr = $info['company'];
        $Email = $info['email'];
        //文献信息
        $docType = 0;
        $journalTitle = $info['doc_name'];
        $pubYear = $info['doc_time'];
        $searchScope = 1;
        //起始页
        $startPage = $endPage = '';
        $doc_page = $info['doc_page'];
        $doc_page = explode('-',$doc_page);
        if(isset($doc_page[0]) && isset($doc_page[1])){
            $startPage = $doc_page[0];
            $endPage = $doc_page[1];
        }
        $pubVol = $info['doc_vol'];
        $docAuthor = $info['doc_author'];
        $agencyOrderID = $info['doc_no'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $id = 'JK＿RES＿LAS';
        $url = "http://168.160.2.50:7001/NSTL/DirectOrderService2Port?wsdl";
        $client = new SoapClient($url);
        /*
        < docType>文献类型(0：一般文献；1：专利；2：标准) (必填)</docType>
        < journalTitle>出版物名称 (必填) (专利号、标准号、会议名)</journalTitle>(出版物名称和文章标题至少填一项，最大长度 512 字符)
        < pubYear>出版年(格式：2001) </pubYear>
        < pubVol>卷(数字)</pubVol>
        < pubNo>期(数字) ISSUE 字段值</pubNo>
        < startPage>起始页(数字,最大长度为 5 位数字)</startPage>
        < endPage>截至页(大于起始页，最大长度为 5 位数字)</endPage>
        < publisher>出版者(字符长度小于 512)</publisher>
        < issn_isbn>ISSN-ISBN(字符长度小于 64)</issn_isbn>
        < docTitle>文献题名(字符长度小于 512)</docTitle>
        < docAuthor>作者(字符长度小于 256)</docAuthor>
        < docLanguage>关键字(字符长度小于 64)</docLanguage>
        < searchScope>查找范围(必填) (0：NSTL 内；1：国内；2：国际)</searchScope>
        < transType>传递方式(M05：自助获取)</transType>
        < priceLimit>费用限制(必填) (0：不限；1：20 元内；2：50 元内；3：100 元内)</priceLimit>
        < timeLimit>时间限制(必填) (0：不限时；1：一周内；2：二周内；3：一月内)</timeLimit>
        < finLibCode>执行单位代码(必填)</finLibCode>
        ( CN111031：中国计量科学研究院文献馆
                CN311001：中国科学院文献情报中心
                CN111001：中国科学技术信息研究所
                CN111013：机械工业信息研究院
                CN111015：冶金工业信息标准研究院
                CN111016：中国化工信息中心
                CN111023：中国农科院农业信息研究所
                CN111024：中国医科院医学信息研究所
                CN111025：中国标准化研究院标准馆)
        < userMemo>用户备注(字符长度小于 1024)</userMemo>
        < agencyOrderID>机构订单 ID (必填，最大长度 30)</agencyOrderID>
        < IP>IP(必填)</ID>
        < ID>接口账号(必填)</ID>
        <SignalSigned>预留字段</SignalSigned>
        <UserInfo>
            <UserName>用户姓名(必填)</UserName>
            <UserTel>联系电话</UserTel>
            <UserAddr>地址</UserAddr>
            <ZipCode>邮编</ZipCode>
            <Email>电子邮箱(必填)</Email>
            <PostType>投递方式(必填：值为 M05)</PostType>
            <UserMemo>用户留言</UserMemo>
        </UserInfo>
        */
        $param = ['arg0' =>
            [
                'agencyOrderID' => $agencyOrderID,
                'docAuthor' => $docAuthor,
                'docLanguage' => '',
                'docTitle' => $journalTitle,
                'docType' => $docType,
                'finLibCode' => 'CN311001',//中国科学院文献情报中心
                'id' => $id,
                'ip' => $ip,
                'issnIsbn' => '',
                'journalTitle' => $journalTitle,
                'priceLimit' => 0,
                'pubNo' => '',
                'pubVol' => $pubVol,
                'pubYear' => $pubYear,
                'publisher' => '',
                'searchScope' => $searchScope,
                'signalSigned' => '',
                'startPage' => $startPage,
                'endPage' => $endPage,
                'timeLimit' => 0,
                'transType' => 'M05',
                'userMemo' => '',
                'userinfo2' => [
                    'email' => $Email,
                    'postType' => 'M05',
                    'userAddr' => $UserAddr,
                    'userMemo' => '',
                    'userTel' => $UserTel,
                    'username' => $username,
                    'zipCode' => ''
                ]
            ]
        ];
        //$response = $client->__getFunctions();var_dump($response);
        //$res = $client->__getTypes();var_dump($res);
        $obj = $client->submitDirectOrderNew($param);
        $array = $this->object_array($obj);
        $result = $array['return']['result'];
        if($result && $array['return']['orderID']){
            $orderID = $array['return']['orderID'];
            $totalBalance = $array['return']['totalBalance'];
            $totalFee = $array['return']['totalFee'];
            $sql = "update uc_literature set relay_status=1, relay_order_id='$orderID' where id=".$literature_id;
            $this->db->query($sql);
            return '转发成功';
        }else{
            $message = $array['return']['result'];
            if($message){
                return $message;
            }
            return '无数据';
        }

    }

    function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = $this->object_array($value);
            }
        }
        return $array;
    }

    //3.4 GetDoc 查询代查代借申请单文献方法
    public function get_doc($OrderID, $AgencyOrderID){
        $url = "http://168.160.2.50:7001/NSTL/DirectOrderService2Port?wsdl";
        $client = new SoapClient($url);
        $ip = $_SERVER['REMOTE_ADDR'];
        $id = 'JK＿RES＿LAS';
        $param = ['arg0' =>
            [
                'OrderID' => $OrderID,
                'AgencyOrderID' => $AgencyOrderID,
                'id' => $id,
                'ip' => $ip,
                'SignalSigned' => ''

            ]
        ];
        //$response = $client->__getFunctions();var_dump($response);
        //$res = $client->__getTypes();var_dump($res);
        $obj = $client->GetDoc($param);
        $array = $this->object_array($obj);
        var_dump($array);exit;
        $result = $array['return']['result'];
        if($result && $array['return']['orderID']){
            $orderID = $array['return']['orderID'];
            $totalBalance = $array['return']['totalBalance'];
            $totalFee = $array['return']['totalFee'];
            $sql = "update uc_literature set relay_status=1, relay_order_id='$orderID' where id=".$literature_id;
            $this->db->query($sql);
            return '转发成功';
        }else{
            $message = $array['return']['result'];
            if($message){
                return $message;
            }
            return '无数据';
        }
    }


}