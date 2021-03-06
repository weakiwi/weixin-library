<?php
/*
    方倍工作室 http://www.cnblogs.com/txw1958/
    CopyRight 2013 www.doucube.com  All Rights Reserved
*/

define("TOKEN", "weixin");
libxml_use_internal_errors(true);
$wechatObj = new wechatCallbackapiTest();
header("content-Type: text/html; charset=gbk");
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

$conn = mysql_connect($servername, $username, $password);//创建sql连接
if(! $conn )
{
  die('Could not connect: ' . mysql_error());
}
mysql_select_db($dbname);
if (isset($_GET['echostr'])) {//这是一些微信的初始化参数
    $wechatObj->valid();
}else{
    $wechatObj->responseMsg();
}

class wechatCallbackapiTest
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];
        if($this->checkSignature()){
            header('content-type:text');
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()//检查请求是不是由你的公众号发出
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    public function responseMsg()//设定回复的信息
    {
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
        function nodelist2string($nodelist) {//把xpath获得的nodelist全部输出为string，仅在只汗一个元素的时候有效
            foreach($nodelist as $node) {
                $a_node = $node->nodeValue;
            }
            return $a_node;
        }
        function dom_parser($html, $mypath) {//构建xpath并查找
            $doc = new DomDocument;
            $doc->loadHTML($html);
            $xpath = new DOMXpath($doc);
            $href = $xpath->query($mypath);
            return nodelist2string($href);
        }
        function test_input($data) {
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data);
            return $data;     
        }     
        function curl_get_contents($url,$timeout=5,$method='get',$post_fields=array(),$reRequest=3,$referer="") { //封装 curl
               $ch = curl_init();
               curl_setopt($ch, CURLOPT_URL, $url);
               curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
               curl_setopt($ch, CURLOPT_HEADER, 0);
               curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
               curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE );
               curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false );
               curl_setopt($ch, CURLOPT_REFERER, $referer);
               if (strpos($method,'post')>-1) {
                   curl_setopt($ch, CURLOPT_POST, true);
                   curl_setopt($ch, CURLOPT_POSTFIELDS,$post_fields);
               }
               if (strpos($method,'WithHeader')>-1) {
                   curl_setopt($ch, CURLOPT_HEADER, true);
                   curl_setopt($ch, CURLOPT_NOBODY, false);
               }
               $output = curl_exec($ch);
               if (curl_errno($ch)==0) {
                   if (strpos($method,'WithHeader')>-1) {
                       $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                       $header = substr($output, 0, $headerSize);
                       $body = substr($output, $headerSize);
                       return array($header,$body,$output);
                   } else {
                       return $output;
                   }
               } else {
                   if ($reRequest) {
                       $reRequest--;
                       return curl_get_contents($url,$timeout,$method,$post_fields,$reRequest);
                   } else {
                       return false;
                   }
               }
           }
        
        if (!empty($postStr)){
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);//初始化处理用户发过来的数据
            $fromUsername = $postObj->FromUserName;
            $toUsername = $postObj->ToUserName;
            $keyword = trim($postObj->Content);
            $event = $postObj->Event;
            $time = time();
            $textTpl = "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[%s]]></MsgType>
                        <Content><![CDATA[%s]]></Content>
                        <FuncFlag>0</FuncFlag>
                        </xml>";
            $bilibili_xml = "
            <xml>
                <ToUserName><![CDATA[%s]]></ToUserName>
                <FromUserName><![CDATA[%s]]></FromUserName>
                <CreateTime>%s</CreateTime>     
                <MsgType><![CDATA[news]]></MsgType>
                <Content><![CDATA[]]></Content>
                <ArticleCount>1</ArticleCount>
                    <Articles>
                        <item>
                            <Title><![CDATA[%s]]></Title>
                            <Description><![CDATA[%s]]></Description>
                                <PicUrl><![CDATA[%s]]></PicUrl>
                                <Url><![CDATA[%s]]></Url>
                        </item>          
                     </Articles>
                <FuncFlag>0</FuncFlag>     
            </xml>
            ";           
            $findbook = 'SELECT * FROM books WHERE book_name="%s"';
            $createbook ='INSERT INTO books (book_isbn, book_author, book_publisher, book_location, book_states, book_ztflh, book_name) VALUES ("%s", "%s","%s","%s","%s","%s","%s")'; 
            $findUser = 'SELECT * from search_history WHERE user="%s";';
            $createuser = 'INSERT INTO search_history (user, counter) VALUES ("%s", 1);';
            $updateCounter = 'UPDATE search_history SET counter = counter+1 WHERE user="%s";';
            $reseteCounter = 'UPDATE search_history SET counter = 0 WHERE user="%s";';
            $findUser = sprintf($findUser, $fromUsername);
            function checktime(){
                    if (intval(date('i'))===0&&intval(date('s'))===0){
                            return 1;
                    }
                    else {
                        return 0;
                    }
            }            
            if ($row = mysql_fetch_array(mysql_query($findUser))) {
                if ($row['counter']<=5) {
                    $updateCounter = sprintf($updateCounter, $fromUsername);
                    $updateCounter = mysql_query($updateCounter);
                }
                elseif (checktime() == 1) {
                    $reseteCounter = 'UPDATE search_history SET counter = 0 WHERE user="%s";';
                    $reseteCounter = mysql_query($updateCounter);
                }
                else {
                $msgType = "text";#回复数据类型为文本
                $contentStr = "超出使用次数，30分钟后恢复";//回复字串内容为年月日
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;//构成标准xml文件                      
                }
            }
            else {
                $createuser = sprintf($createuser, $fromUsername);
                $createuser = mysql_query($createuser);
            }
            if($event == "subscribe") {
                $msgType = "text";#回复数据类型为文本
                $contentStr = "使用方法：book+书籍全名\n如：'book+三国演义'";//回复字串内容为年月日
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;//构成标准xml文件                
            }
            if($keyword == "?" || $keyword == "？")//当url带问号的时候执行花括号内的内容
            {
                $msgType = "text";#回复数据类型为文本
                $contentStr = date("Y-m-d H:i:s",time());//回复字串内容为年月日
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;//构成标准xml文件
            }
            if(strstr($keyword,"支不支持"))
            {
                $msgType = "text";#回复数据类型为文本
                $contentStr = "支持啊";//回复字串内容为年月日
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;//构成标准xml文件
            }  
            if(strstr($keyword,"book+"))
            {
                $search = str_replace("book+","",$keyword);
                $sql = sprintf($findbook, $search);
                $findbook = mysql_query($sql);
                if ($row = mysql_fetch_array($findbook)){
                    $resultStr = sprintf($bilibili_xml, $fromUsername, $toUsername, $time,  $row['book_name'] . "\n",
                                                                                            $row['book_author'] . "\n" 
                                                                                          . $row['book_states'] . "\n"
                                                                                          . $row['book_location'] . "\n"
                                                                                          . $row['book_states'] . "\n"
                                                                                          . $row['book_ztflh']
                                                                                          . $row['book_publisher'] , "", "http://115.28.1.228/a.php?isbn=" );
                    echo $resultStr;
                }
                else {
                    $url = "http://202.118.84.130:1701/primo_library/libweb/action/search.do?ct=facet&fctN=facet_tlevel&fctV=available&rfnGrp=show_only&dscnt=0&frbg=&scp.scps=scope%3A(DLMH)%2Cprimo_central_multiple_fe&tab=default_tab&dstmp=1456801597218&srt=rank&ct=search&mode=Basic&&dum=true&indx=1&vl(freeText0)=".$search;
                    $url = $url."&fn=search&vid=dlmh";//海事大学图书馆书名查找入口
                    $html_source = curl_get_contents($url);
                //    echo dom_parser($html_source, "//li[@id='exlidResult0-LocationsTab']/a/@href");//获取图书所在位置
                    $url = "http://202.118.84.130:1701/primo_library/libweb/action/" . dom_parser($html_source, "//li[@id='exlidResult0-LocationsTab']/a/@href");//查找图书所在位置(在架状态页面)
                    $url_2 = "http://202.118.84.130:1701/primo_library/libweb/action/" . dom_parser($html_source, "//a[@id='exlidResult0-detailsTabLink']/@href");//查找图书所在位置(详细信息页面)
                    $html_source_2 = curl_get_contents($url_2);
                    $book_isbn = dom_parser($html_source_2, "//ul/li[@id='识别符-1']/span[@class='EXLDetailsDisplayVal']");
                    $html_source = curl_get_contents($url);
                    $book_name = dom_parser($html_source, "//h1[@class='EXLResultTitle']");//获取书籍名称
                    $book_author = dom_parser($html_source_2,"//ul/li[@id='著者-1']/a[@class='EXLLinkedField']");//获取书籍作者
                    $book_publisher = dom_parser($html_source_2,"//ul/li[@id='出版发行-1']/span[@class='EXLDetailsDisplayVal']");//获取书籍出版社
                    $book_location = trim(dom_parser($html_source, "//span[@class='EXLLocationsTitleContainer']"));//获取书籍所在位置
                    $book_details = dom_parser($html_source, "//h3[@class='EXLResultFourthLine']");//获取书籍细节     
                    $book_states = dom_parser($html_source, "//td[@class='EXLLocationTableColumn3']");//获取在架状态
                    $book_ztflh = dom_parser($html_source, "//cite");//获取书籍中图分类号
                    $pa = '{[a-zA-Z]{1,2}.*[0-9]}';
                    if (preg_match($pa, $book_ztflh, $a_book_ztflh)) {
                        $book_ztflh = $a_book_ztflh[0];
                    }
                    $contentStr = $book_location . "\n" . $book_states . "\n" . $book_ztflh . "\n" . $book_author . "\n" . $book_publisher . "\n" . $book_isbn . "\n" ;
                    if (ctype_space($contentStr)){
                        $contentStr = "没有你想要找的书籍";
                    }
                    $createbook = sprintf($createbook, $book_isbn, $book_author, $book_publisher, $book_location, $book_states, $book_ztflh, $book_name);
                    mysql_query($createbook);                  
                    $resultStr = sprintf($bilibili_xml, $fromUsername, $toUsername, $time,  $book_name, $contentStr, "", "http://115.28.1.228/a.php?isbn=" );
                    echo $resultStr;//
                }  
                mysql_close();
            }
        }   
        else{
                $msgType = "text";#回复数据类型为文本
                $contentStr = "也要准守基本法";//回复字串内容为年月日
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;//构成标准xml文件
        }
    }
}
?>
