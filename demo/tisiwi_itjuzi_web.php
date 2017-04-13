<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';


/* Do NOT delete this comment */
/* 不要删除这段注释 */


$configs = array(
    'name' => 'itjuzi.com',
    'tasknum' => 1, //同时工作的爬虫任务数,需要配合redis保存采集任务数据，供进程间共享使用
    'save_running_state' => false, //保存爬虫运行状态
    'log_show' => true,
    'interval' => 1000,
    'timeout' => 60,
    'user_agent' => phpspider::AGENT_PC,
    'client_ips' => array(
        '111.73.240.202:9000',
        '124.206.254.216:3128',
        '112.249.41.57:8888',
    ),
    'domains' => array(
        'itjuzi.com',
        'www.itjuzi.com',
    ),
    'scan_urls' => array( //入口链接
        "http://www.itjuzi.com/company",
//        "http://www.itjuzi.com/company?fund_status=5",
    ),
    'list_url_regexes' => array(  //定义列表页url的规则
        "http://www.itjuzi.com/company\?page=\d+",
//    "http://www.itjuzi.com/company?fund_status=5&page=\d+",

    ),
    'content_url_regexes' => array( //定义内容页url的规则
        "http://www.itjuzi.com/company/\d+",
    ),
    'export' => array( //爬虫爬取数据导出
        'type' => 'db',
        'table' => 'tisiwi_items_pool',
        //'type' => 'csv',
        //'file' => PATH_DATA.'/jd_goods.csv',
    ),
    'fields' => array(  //定义内容页的抽取规则
        // 项目ID
        array(
            'name' => "item_id",
            'selector' => '//*[@id="modal_myinc"]/div/div/div[2]/div/form/input/@value',
            'required' => true,
        ),
        // 项目名
        array(
            'name' => "item_name",
            'selector' => "//ul[contains(@class,'bread dark')]//li[3]/a/text()",
            'required' => true,
        ),
        // 项目item_logo
        array(
            'name' => "item_logo",
            'selector' => "//div[contains(@class,'rowhead')]/div[contains(@class,'pic')]/img/@src",
            'required' => true,
        ),
        // 项目item_brief
        array(
            'name' => "item_brief",
            'selector' => "//div[contains(@class,'des')]/text()",
            'required' => true,
        ),
        // 项目item_area
        array(
            'name' => "item_area",
            'selector' => "//div[contains(@class,'tagset dbi c-gray-aset')]",
            'required' => true,
        ),
        // 项目item_CEO
        array(
            'name' => "item_CEO",
            'selector' => "//a[contains(@class,'title')]//span[contains(@class,'c')]/text()",
            'required' => true,
        ),
        // 项目item_round
        array(
            'name' => "item_round",
            'selector' => "//span[contains(@class,'t-small c-green')]/text()",
            'required' => true,
        ),
        // 项目item_website
        array(
            'name' => "item_website",
            'selector' => "//a[contains(@class,'weblink')]/@href",
            'required' => true,
        ),
        // 项目item_from
        array(
            'name' => "item_from",
            'selector' => "//a[contains(@class,'')]",
            'required' => false,
        ),
        // 项目item_from_website
        array(
            'name' => "item_from_website",
            'selector' => "//a[contains(@class,'')]",
            'required' => false,
        ),
        // 项目item_address
        array(
            'name' => "item_address",
            'selector' => "//span[contains(@class,'loca c-gray-aset')]",
            'required' => true,
        ),


    ),
);

$spider = new phpspider($configs);
$spider->on_start = function($phpspider)
{
    $proxies = array(
//        'http' => '117.90.4.233:9000',
//        'https' => 'http://user:pass@host:port',
    );
    requests::set_proxies($proxies);
};
$spider->is_anti_spider = function($url, $content, $phpspider)
{
    // $content中包含"404页面不存在"字符串
    if (strpos($content, "404页面不存在") !== false)
    {
        // 如果使用了代理IP，IP切换需要时间，这里可以添加到队列等下次换了IP再抓取
         $phpspider->add_url($url);
        return true; // 告诉框架网页被反爬虫了，不要继续处理它
    }
    // 当前页面没有被反爬虫，可以继续处理
    return false;
};
$spider->on_status_code = function($status_code, $url, $content, $phpspider)
{
    // 如果状态码为429，说明对方网站设置了不让同一个客户端同时请求太多次
    if ($status_code == '429')
    {
        // 将url插入待爬的队列中,等待再次爬取
        $phpspider->add_url($url);
        // 当前页先不处理了
        return false;
    }
    // 不拦截的状态码这里记得要返回，否则后面内容就都空了
    return $content;
};
/**
 * @param $fieldname
 * @param $data 当前field抽取到的数据
 * @param $page 当前下载的网页页面的对象
 *  @param $page['url'] 当前网页的URL
 *  @param $page['raw'] 当前网页的内容
 *  @param $page['request'] 当前网页的请求对象
 * @return mixed|string
 */
$spider->on_extract_field = function($fieldname, $data, $page){
    if($fieldname == 'item_brief'){
        $data = trim($data);
    }elseif ($fieldname == 'item_from'){
        $data = 'IT桔子';
    }elseif ($fieldname == 'item_phone'){
        $data = '';
    }elseif ($fieldname == 'item_email'){
        $data = '';
    }elseif ($fieldname == 'item_from_website'){
        $data = $page['request']['url'];
    }elseif ($fieldname == 'item_round'){
        $data = str_replace("(","",trim($data));
        return  str_replace(")","",trim($data));
//        if(
//            strstr(trim($data),'尚未获投') != false ||
//            strstr(trim($data),'种子') != false ||
//            strstr(trim($data),'天使') != false ||
//            strstr(trim($data),'Pre-A') != false ||
//            strstr(trim($data),'不明确') != false
//        ){
//            file_put_contents(PATH_DATA.'/1.txt',trim($data),FILE_APPEND);
//            file_put_contents(PATH_DATA.'/1.txt',"\r\n",FILE_APPEND);
//        }

    }elseif ($fieldname == 'item_area'){
        $str = strip_tags($data,"");
        $str = preg_replace("/\t/","",$str);
        $str = preg_replace("/\r\n/","",$str);
        $str = preg_replace("/\r/","",$str);
        $str = preg_replace("/\n/","、",$str);
        $strarr = explode('、',$str);
        $arr = array();
        foreach ($strarr as $item) {
            $item = trim($item);
            if(!empty($item)){
                $arr[] = $item;
            }
        }
        $data = implode('、',$arr);

    }elseif ($fieldname == 'item_address'){
        $str = strip_tags($data,"");
        $str = preg_replace("/\t/","",$str); //使用正则表达式替换内容，如：空格，换行，并将替换为空。
        $str = preg_replace("/\r\n/","",$str);
        $str = preg_replace("/\r/","",$str);
        $str = preg_replace("/\n/","、",$str);
        $strarr = explode('、',$str);
        $arr = array();
        foreach ($strarr as $item) {
            $item = trim($item);
            if(!empty($item) && $item !== '·'){
                $arr[] = $item;
            }
        }
        $data = implode('、',$arr);
//        file_put_contents(PATH_DATA.'/1.txt',$data,FILE_APPEND);
//        file_put_contents(PATH_DATA."\r\n",$data,FILE_APPEND);
    }

    return $data;
};

$spider->start();
/*
CREATE TABLE `tisiwi_items_pool`(
    `item_id` bigint(20) unsigned NOT NULL DEFAULT '0' COMMENT '项目id',
    `item_name` varchar(255) NOT NULL DEFAULT '' COMMENT '项目名称',
    `item_logo` varchar(255) NOT NULL DEFAULT '' COMMENT '项目logo',
    `item_brief` text COMMENT '简介',
    `item_area` text COMMENT '领域',
    `item_from` varchar(20) NOT NULL DEFAULT '' COMMENT '来源',
    `item_CEO` varchar(50) NOT NULL DEFAULT '' COMMENT 'CEO',
    `item_phone` varchar(20)  DEFAULT '' COMMENT '电话',
    `item_email` varchar(20)  DEFAULT '' COMMENT '邮箱',
    `item_website` varchar(255)  DEFAULT '' COMMENT '官网',
    `item_from_website` varchar(255)  DEFAULT '' COMMENT '项目展示网站',
    `item_address` varchar(255)  DEFAULT '' COMMENT '地址',
    `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '抓取时间',
    `update_user` bigint(20) NOT NULL DEFAULT '0' COMMENT '用户id',
    PRIMARY KEY (`item_id`,`item_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='天使湾项目表';
*/
?>

