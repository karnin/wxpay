<?php

namespace  Karnin\WxPay;
class Wxpay
{
    private $config = array(
        'appid' => "",    /*微信开放平台上的应用id*/
        'mch_id' => "",   /*微信申请成功之后邮件中的商户id*/
        'api_key' => "",    /*在微信商户平台上自己设定的api密钥 32位*/
        'notify_url' => '' /*自定义的回调程序地址id*/
    );

    public function __construct($config){
         $this->config=$config;
    }
    public function unifiedorder($data){
        $url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
        $data["appid"] = $this->config["appid"];
        $data["mch_id"] = $this->config['mch_id'];
        $data["notify_url"] =  $this->config['notify_url'];
        $data["trade_type"] = "APP";
        $data["nonce_str"] = $this->getNonceStr();
        $data["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];

        $s = $this->sign($data);
        $data["sign"] = $s;


        $xml=$this->xml($data);

        $response = $this->postXmlCurl($xml, $url);


        libxml_disable_entity_loader(true);
        $result = json_decode(json_encode(simplexml_load_string($response, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        return $result;
    }

    public  function sign($data){
        //签名步骤一：按字典序排序参数
        ksort($data);

        $buff = "";
        foreach ($data as $k => $v)
        {
            if($k != "sign" && $v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }

        $string = trim($buff, "&");

        //签名步骤二：在string后加入KEY
        $string = $string . "&key=".$this->config['api_key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }
    public  function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    public function xml($data)
    {


        $xml = "<xml>";
        foreach ($data as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

    private  function postXmlCurl($xml, $url,$second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,TRUE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);


        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if($data){
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            curl_close($ch);
            return false;
        }
    }
}