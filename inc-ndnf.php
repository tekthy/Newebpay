<?php

 //***** SDK程式庫 inc_ndnf.php 
 //***** For Newebpay NDNF
 //***** Tek 2022-9-26 
 //***** Sharon接力 2022-10-04 
 //***** Sharon 2022-10-25新增單串完成
 //***** Tek 2022-10-27 
 
 //公用區 utility
 
 //去除Padding
 function strippadding($string) {
        $slast = ord(substr($string, -1)); 
        $slastc = chr($slast); 
        $pcheck = substr($string, -$slast); 
        if (preg_match("/$slastc{" . $slast . "}/", $string)) { 
            $string = substr($string, 0, strlen($string) - $slast); 
            return $string; 
        } else { 
            return false; 
        } 
}

//解碼
 function udec($name)
    {
    $json = '{"str":"'.$name.'"}';
    $arr = json_decode($json,true);
    if(empty($arr)) return ''; 
    return $arr['str'];
    }


 function DEAES256($k,$i,$str) 
 {
  return strippadding(openssl_decrypt(hex2bin($str), "AES-256-CBC", $k, OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, $i));
 }
 
 //基礎區 - 不要更動這裡
 
 //基礎API Interface
 //均需開發:
 //encreq(): 產生加密及Hash字串
 //gdt(): 回傳物件參數
 //shwf(): 顯示範例Form Post
 //curf(): 透過curl 直接發動API
	
	interface IAPI
	{	 
		function EncReq();
		public function gdt($which);
		public function ShwF($zone);
		public function CurF($zone);
	} //基礎API Interface - 結束 

 //基礎API 模型 
 //包括各項變數 
 class BAPI
 {
  protected $HashKey="";
  protected $HashIV="";
  protected $RawReq="";
  protected $EncReq="";
  protected $Result="";
  protected $Message="";
  protected $Status="";
  protected $MerchantOrderNo="";  
  
  function __construct()
  {	  
  }
  
     public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
 
    if($which=="TradeInfo")
	 return $this->EncReq["TradeInfo"];
 
    if($which=="TradeSha")
     return $this->EncReq["TradeSha"];
 
    if($which=="HashKey")
     return $this->HashKey;
 
	if($which=="HashIV")
     return $this->HashIV;
 
   }
 }
//基礎API類別 - 結束
 
 
//第一支 NPAF01-MPG
 class NPAF01 extends BAPI implements IAPI
 {
  
//建構子
  function __construct($k,$i,$m,$amt,$p='',$idsc='產品名稱',$cmt='商店備註',$nurl='',$rurl='') 
  {
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   
   if($p=="")
	$this->Prefix='MyOrder'.time();
   else
	$this->Prefix=$p;
   
  $this->RawReq=http_build_query(array(
 'MerchantID'=>$this->mid,
 'TimeStamp'=>time(),
 'Version'=>'2.0',
 'RespondType'=>'JSON', 
 'MerchantOrderNo'=>$this->Prefix,
 'Amt'=>$amt,
 'OrderComment'=>$cmt,
 'ItemDesc'=>$idsc, 
 'NotifyURL'=>$nurl,
 'ReturnURL'=>$rurl,  
 'ImageUrl'=>'',
   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
 
   
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 $hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
//return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
//顯示商店資訊
  public function show()
  {
   echo "<br>[商店資訊]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HashedKey=[".$this->HashKey."]<br>";
   echo "HashedIV=[".$this->HashIV."]<br>";
   echo "訂單資訊=[".$this->Prefix."]<br>";
  }    
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[NPA-F01 Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/MPG/mpg_gateway';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/MPG/mpg_gateway';
  ?>
 <script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
 </script> 
 
 <form method=post action="<?=$aurl?>"> 
	MPG Version: 2.0
	MID: <?=$this->gdt("mid")?>
	交易單號: <?=$this->Prefix?>
	<input type=hidden name="MerchantID" value="<?=$this->gdt("mid")?>" readonly><br>
	<input type=hidden name="Version" value="2.0" readonly><br>
	<input type=hidden name="TradeInfo" value="<?=$this->gdt("TradeInfo")?>" readonly><br>
	<input type=hidden name="TradeSha" value="<?=$this->gdt("TradeSha")?>" readonly><br>
	<input type=submit> 
 </form> 
 
 
 <?php
}

 public function CurF($zone)
   {
 //End of NPAF01
   }
 }
 
 
 
 
 
//第二支 NPAB02-單筆交易查詢
 class NPAB02 extends BAPI implements IAPI
 {
	 protected $amt="";  
  
//建構子
  function __construct($k,$i,$m,$mon,$a) 
  {
   echo "<br>[建立NPA-B02物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->MerchantOrderNo=$mon;
   $this->amt=$a;
   
   $this->RawReq=http_build_query(array(
 'Amt'=>$this->amt,
 'MerchantID'=>$this->mid,
 'MerchantOrderNo'=>$this->MerchantOrderNo,
   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
	if($which=="result")
   return $this->result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;

   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 //$hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hashs="IV=".$this->HashIV."&".$this->RawReq."&Key=".$this->HashKey;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
 
//顯示商店資訊
  public function show()
  {
   echo "<br>[單筆交易查詢]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[NPA-B02 Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/QueryTradeInfo';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/QueryTradeInfo';
  ?>
  
 <form id="aa" method=post action="<?=$aurl?>">
  商店: <input name="MerchantID" value="<?=$this->gdt("mid")?>" readonly><br>
  版號: <input name="Version" value="1.3" readonly><br>
  回傳型態會是:<input name="RespondType" value="JSON" readonly><br>
  檢查碼:<input name="CheckValue" value="<?=$this->EncReq["TradeSha"]?>" readonly><br>
  時間戳記:<input name="TimeStamp" value="<?=time()?>" readonly><br>
  商店訂單編號:<input name="MerchantOrderNo" value="<?=$this->MerchantOrderNo?>" readonly><br>
  金額:<input name="Amt" value="<?=$this->amt?>" readonly><br>

<input type=submit>
<?php
   }

public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/QueryTradeInfo?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/QueryTradeInfo?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
 'MerchantID'=>$this->mid,
 'TimeStamp'=>time(),
 'Version'=>'2.0',
 'RespondType'=>'JSON', 
 'MerchantOrderNo'=>$this->MerchantOrderNo, 
 'Amt'=>$this->amt,	
 "CheckValue" => $this->EncReq["TradeSha"]
	
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
  
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
     }
   //echo "R=".$result;
   $this->result=json_decode($result,true);
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true);
   if(json_decode($result,true)["Result"]) //如果有成功
   curl_close($ch); 
 //curl 結束
   }
 }   //End of NPAB02
 
 

//第三支 NPAB031-請退款交易
 class NPAB031 extends BAPI implements IAPI
{

//建構子
  function __construct($k,$i,$m,$mon,$a,$Ct,$Cancel="") 
  {
  echo "<br>[建立NPA-B031物件]";
  $this->mid=$m;
  $this->HashIV=$i;
  $this->HashKey=$k;
  $this->CloseType=$Ct;
  $this->amt=$a;
  $this->MerchantOrderNo=$mon;
  $this->Cancel=$Cancel;
  
  $this->RawReq=http_build_query(array(
 'RespondType'=>'JSON',
 'Version'=>'1.1',
 'Amt'=>$this->amt,
 'MerchantOrderNo'=>$this->MerchantOrderNo,
 'TimeStamp'=>time(),
 'IndexType'=>'1',//MerNO, or TradeNo
 //請款 B031 / 取消請款 B033 時請填 1
 //退款 B032 / 取消退款 B034 時請填 2
 'CloseType'=>$this->CloseType, 
 'Cancel'=>$this->Cancel,

   )); //產Request字串   
   $this->EncReq();
  }  
  
 //取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
	if($which=="result")
   return $this->result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 $hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }  
 
  //顯示商店資訊
  public function show()
  {
   echo "<br>[請退款交易]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/CreditCard/Close';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/CreditCard/Close';
  ?> 
  
<form id="aa" method=post action="<?=$aurl?>">
MID:<input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
PostData_:<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit value='Submit'>
</form>

<?php
   }
public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/CreditCard/Close?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/CreditCard/Close?callback=';
 
  //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"]
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
  
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->result=json_decode($result,true);
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true);
   if(json_decode($result,true)["Result"]) //如果有成功
   curl_close($ch); 
 //curl 結束
   }
 }   //End of NPAB031
  
  
 
//第四支 NPAB01-取消交易
 class NPAB01 extends BAPI implements IAPI
{

//建構子
  function __construct($k,$i,$m,$mon,$a) 
  {
  echo "<br>[建立NPA-B01物件]";
  $this->mid=$m;
  $this->HashIV=$i;
  $this->HashKey=$k;
  $this->amt=$a;
  $this->MerchantOrderNo=$mon;
  
  $this->RawReq=http_build_query(array(
'RespondType'=>'JSON',
'Version'=>'1.0',
'TimeStamp'=>time(),
'Amt'=>$this->amt,
'MerchantOrderNo'=>$this->MerchantOrderNo,
'IndexType'=>'1', 
   )); //產Request字串   
   $this->EncReq();
  }  
  
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
	if($which=="result")
   return $this->result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 $hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }  
   
//顯示商店資訊
  public function show()
  {
   echo "<br>[商店資訊]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
  }    

   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/CreditCard/Cancel';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/CreditCard/Cancel';
  ?>

<form id="aa" method=post action="<?=$aurl?>">
MID:<input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
PostData_:<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit value='Submit'>
</form>
<?php
   }

 public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/CreditCard/Cancel?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/CreditCard/Cancel?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"]
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
  
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->result=json_decode($result,true);
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true);
   if(json_decode($result,true)["Result"]) //如果有成功
   curl_close($ch); 
 //curl 結束
   }
 }   //End of NPAB01
 


//第四支 NPAB06-電子錢包退款
 class NPAB06 extends BAPI implements IAPI
{

//建構子
  function __construct($k,$i,$m,$mon,$a,$pt) 
  {
  echo "<br>[建立NPA-B06物件]";
  $this->mid=$m;
  $this->HashIV=$i;
  $this->HashKey=$k;
  $this->amt=$a;
  $this->MerchantOrderNo=$mon;
  $this->PaymentType=$pt;
  
  $this->RawReq=json_encode(array(
'TimeStamp'=>time(),
'Amount'=>$this->amt,
'MerchantOrderNo'=>$this->MerchantOrderNo,
'PaymentType'=>$this->PaymentType, 
   )); //產Request字串
   $this->EncReq();
  }  
  
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
 	if($which=="result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;

   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 $hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
//return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   } 
   
//顯示商店資訊
  public function show()
  {
   echo "<br>[商店資訊]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
  }    

   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/EWallet/refund';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/EWallet/refund';
  ?>

<form id="aa" method=post action="<?=$aurl?>">
MID:<input name="UID_" value="<?=$this->gdt("mid")?>" readonly><br>
Version_: <input name="Version_" value="1.0"><br>
EncryptData_: <input name="EncryptData_" value="<?=$this->EncReq["TradeInfo"]?>"><br>
RespondType: <input name="RespondType_" value="JSON"><br>
HashData_: <input name="HashData_" value="<?=$this->EncReq["TradeSha"]?>"><br>
<input type=submit value='Submit'>
</form>
<?php
   }

 public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/EWallet/refund?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/EWallet/refund?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "UID_" => $this->gdt("mid"),
    "Version_" => '1.0',
	"EncryptData_" => $this->EncReq["TradeInfo"],
	"RespondType_" => 'JSON',
	"HashData_" => $this->EncReq["TradeSha"]
	
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
  
   //curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   curl_close($ch);  //curl 結束
   
   //執行結果在$result
   //echo "R=".$result;
   $this->result=json_decode($result,true);
   //echo "執行結果:";
   if(json_decode($result,true)["EncryptData"]) //如果有成功
   {
   $this->Result=json_decode(DEAES256($this->HashKey,$this->HashIV,json_decode($result,true)["EncryptData"]),true);

   }

 }   //End of NPAB06 
}


 //單串幕後 NPAB10-CreditCard
 class NPAB10 extends BAPI implements IAPI
 {
  
//建構子
  function __construct($k,$i,$m,$amt,$Pmail,$p='',$Pdsc='產品名稱',$cmt='商店備註',$rurl='',$nurl='') 
  {
   echo "<br>[建立NPAB10物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->Prefix=$p; 
   $this->PayerEmail=$Pmail;
 
   $this->RawReq=http_build_query(array(
	'TimeStamp'=>time(),
	'Version'=>'1.1',
	'P3D'=>'0',
	'NotifyURL'=>$nurl,
	'ReturnURL'=>$rurl, 
	'MerchantOrderNo'=>$this->Prefix.time(),
	'Amt'=>$amt,
	'ProdDesc'=>$Pdsc,
	'PayerEmail'=>$Pmail,
	'CardNo'=>'4000221111111111',
	'Exp'=>'2411',
	'CVC'=>'123',

   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
 	if($which=="Result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;

   }
 
   
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 $hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
//return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
//顯示商店資訊
  public function show()
  {
   echo "<br>[商店資訊]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
   echo "Prefix=[".$this->Prefix."]<br>";
  }    


//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/CreditCard';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/CreditCard';
  ?>
  
 <script>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.1/jquery.min.js"></script>
 </script> 
<form id="aa"method=post action="<?=$aurl?>"> 
MerchantID_: <input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
PostData_: <input name="PostData_" value="<?=$this->gdt("TradeInfo")?>" readonly><br>
Pos_: <input name="Pos_" value="JSON" readonly><br>
<input type=submit>
</form> 
<?php
}

public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/CreditCard?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/CreditCard?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"],
    "Pos_" => "JSON",
  
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
  
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->Result=json_decode($result,true)["Result"];
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true)["Status"];

   curl_close($ch);
   }
 }//NPAB10結束
 
 
 
 
 
//單串 NPAB11-WebATM
 class NPAB11 extends BAPI implements IAPI
 {
   
//建構子
  function __construct($k,$i,$m,$a,$Pdsc='商品描述',$mon='Order') 
  {
   echo "<br>[建立NPA-B11物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->MerchantOrderNo=$mon;
   $this->amt=$a;
   
   $this->RawReq=http_build_query(array(
  'RespondType'=>'JSON',
  'Version'=>'1.0',
  'ProdDesc'=>$Pdsc,
  'Amt'=>$this->amt,
  'MerchantOrderNo'=>$this->MerchantOrderNo.time(),
  'TimeStamp'=>time(),
  'ReturnURL'=>"https://cwww.newebpay.com/",
  'NotifyURL'=>"https://wcmoc.line-fans.com/wp_notified.php?id=sharon&API=NPA-B11",
   )); //產Request字串   
   $this->EncReq();
  }  
 
 //取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
	if($which=="Result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 //$hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hashs="IV=".$this->HashIV."&".$this->RawReq."&Key=".$this->HashKey;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
//顯示商店資訊
  public function show()
  {
   echo "<br>[單筆交易查詢]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/gateway/webatm';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/gateway/webatm';
  ?>
  
 <form id="aa" method=post action="<?=$aurl?>">
  MerchantID_ : <input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
  PostData_ :<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit>
<?php
   }

 public function CurF($zone)
   {

 //End of NPAB11-WebATM
   }
 }  
  


 //單串 NPAB12-ATM
 class NPAB12 extends BAPI implements IAPI
 {
  
//建構子
  function __construct($k,$i,$m,$a,$mail,$Pdsc='商品描述',$mon='Order') 
  {
   echo "<br>[建立NPA-B12物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->MerchantOrderNo=$mon;
   $this->amt=$a;
   $this->Email=$mail;
   
   $this->RawReq=http_build_query(array(
  'RespondType'=>'JSON',
  'TimeStamp'=>time(),
  'Version'=>'1.0',
  'MerchantOrderNo'=>$this->MerchantOrderNo.time(),
  'Amt'=>$this->amt,
  'ProdDesc'=>$Pdsc,
  'Email'=>$mail,
 
 
   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
 	if($which=="Result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 //$hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hashs="IV=".$this->HashIV."&".$this->RawReq."&Key=".$this->HashKey;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
 
//顯示商店資訊
  public function show()
  {
   echo "<br>[單筆交易查詢]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
   
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/gateway/vacc';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/gateway/vacc';
  ?>
  
 <form id="aa" method=post action="<?=$aurl?>">
  MerchantID_ : <input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
  PostData_ :<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit>
<?php
   }

 public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/gateway/vacc?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/gateway/vacc?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"],
  
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
   
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->Result=json_decode($result,true)["Result"];
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true)["Status"];

   curl_close($ch); 
   //End of NPAB12 
 }
 }
 
  //單串幕後 NPAB13-超商代碼
 class NPAB13 extends BAPI implements IAPI
 {
  
//建構子
  function __construct($k,$i,$m,$a,$mail,$Pdsc='商品描述',$mon='Order') 
  {
   echo "<br>[建立NPA-B13物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->MerchantOrderNo=$mon;
   $this->amt=$a;
   $this->Email=$mail;
   
   $this->RawReq=http_build_query(array(
  'RespondType'=>'JSON',
  'TimeStamp'=>time(),
  'Version'=>'1.0',
  'MerchantOrderNo'=>$this->MerchantOrderNo.time(),
  'Amt'=>$this->amt,
  'ProdDesc'=>$Pdsc,
  'Email'=>$mail,
 
 
   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
 	if($which=="Result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 //$hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hashs="IV=".$this->HashIV."&".$this->RawReq."&Key=".$this->HashKey;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
 
//顯示商店資訊
  public function show()
  {
   echo "<br>[單筆交易查詢]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
   
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/gateway/cvs';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/gateway/cvs';
  ?>
  
 <form id="aa" method=post action="<?=$aurl?>">
  MerchantID_ : <input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
  PostData_ :<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit>
<?php
   }

 public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/gateway/cvs?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/gateway/cvs?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"],
  
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
   
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->Result=json_decode($result,true)["Result"];
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true)["Status"];

   curl_close($ch); 
   //End of NPAB13-超商代碼 
 }
 }
 
   //單串幕後 NPAB14-條碼繳費
 class NPAB14 extends BAPI implements IAPI
 {
  
//建構子
  function __construct($k,$i,$m,$a,$mail,$Pdsc='商品描述',$mon='Order') 
  {
   echo "<br>[建立NPA-B13物件]";
   $this->mid=$m;
   $this->HashIV=$i;
   $this->HashKey=$k;
   $this->MerchantOrderNo=$mon;
   $this->amt=$a;
   $this->Email=$mail;
   
   $this->RawReq=http_build_query(array(
  'RespondType'=>'JSON',
  'TimeStamp'=>time(),
  'Version'=>'1.0',
  'MerchantOrderNo'=>$this->MerchantOrderNo.time(),
  'Amt'=>$this->amt,
  'ProdDesc'=>$Pdsc,
  'Email'=>$mail,
 
 
   )); //產Request字串   
   $this->EncReq();
  }  
 
//取得內部參數, which 可為mid, TradeInfo, TradeSha 
   public function gdt($which)
   {
    if($which=="mid")
     return $this->mid;
    if($which=="TradeInfo")
   return $this->EncReq["TradeInfo"];
    if($which=="TradeSha")
   return $this->EncReq["TradeSha"];
 	if($which=="Result")
   return $this->Result;
	if($which=="Message")
   return $this->Message;
    if($which=="Status")
   return $this->Status;
   }
 
   public function EncReq()
   {
 $edata1=bin2hex(openssl_encrypt($this->RawReq, "AES-256-CBC", $this->HashKey, OPENSSL_RAW_DATA, $this->HashIV));
 //$hashs="HashKey=".$this->HashKey."&".$edata1."&HashIV=".$this->HashIV;
 $hashs="IV=".$this->HashIV."&".$this->RawReq."&Key=".$this->HashKey;
 $hash=strtoupper(hash("sha256",$hashs)); 
 $this->EncReq=array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
 //return array("TradeInfo"=>$edata1,"TradeSha"=>$hash);
   }
   
 
//顯示商店資訊
  public function show()
  {
   echo "<br>[單筆交易查詢]<br>";
   echo "mid=".$this->mid."<br>";
   echo "HK=[".$this->HashKey."]<br>";
   echo "HI=[".$this->HashIV."]<br>";
   
  }    
   
   
//產生範例FormPost
   public function ShwF($zone)
  {
   echo "<br>[範例版Form Post]";
   if(strtoupper($zone)=="C")
  $aurl='https://ccore.newebpay.com/API/gateway/barcode';
   if(strtoupper($zone)=="P")
  $aurl='https://core.newebpay.com/API/gateway/barcode';
  ?>
  
 <form id="aa" method=post action="<?=$aurl?>">
  MerchantID_ : <input name="MerchantID_" value="<?=$this->gdt("mid")?>" readonly><br>
  PostData_ :<input name="PostData_" value="<?=$this->EncReq["TradeInfo"]?>" readonly><br>
<input type=submit>
<?php
   }

 public function CurF($zone)
   {
  if(strtoupper($zone)=="C")
   $aurl='https://ccore.newebpay.com/API/gateway/barcode?callback=';
  if(strtoupper($zone)=="P")
   $aurl='https://core.newebpay.com/API/gateway/barcode?callback=';
 
 //送給API交易資訊
   $this->RawReqF= array(
    "MerchantID_" => $this->mid,
    "PostData_" => $this->EncReq["TradeInfo"],
  
   );
   
   
//curl預備
   $curl_options = array(
    CURLOPT_URL => $aurl,
    CURLOPT_HEADER => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Google Bot',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_POST => '1',
    CURLOPT_POSTFIELDS => $this->RawReqF,
   );
   
//curl開始
   $ch = curl_init();
   curl_setopt_array($ch, $curl_options);
   $result = curl_exec($ch);
   $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
   $curl_error = curl_errno($ch);
   
   if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == '200') 
   {
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($result, 0, $headerSize);
    $result = substr($result, $headerSize);  
   }
   //echo "R=".$result;
   $this->Result=json_decode($result,true)["Result"];
   //echo "執行結果:";
   $this->Message=json_decode($result,true)["Message"]; //執行結果訊息塞到在$this->Message
   $this->Status=json_decode($result,true)["Status"];

   curl_close($ch); 
   //End of NPAB14-條碼繳費 
 }
 }