<?php
ini_set("memory_limit", "1024M");
require dirname(__FILE__).'/../core/init.php';


/* Do NOT delete this comment */
/* 不要删除这段注释 */


$configs = array(
    'name' => 'newseed',
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
        'pedaily.cn',
        'newseed.pedaily.cn',
    ),
    'scan_urls' => array( //入口链接
        "http://newseed.pedaily.cn/company",
    ),
    'list_url_regexes' => array(  //定义列表页url的规则
        "http://newseed.pedaily.cn/company/p\d+",
    ),
    'content_url_regexes' => array( //定义内容页url的规则
        "http://newseed.pedaily.cn/company/\d+",
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
            'selector' => "//span[contains(@id,'view-pv')]/@id",
            'required' => true,
        ),
        // 项目名
        array(
            'name' => "item_name",
            'selector' => "//div[contains(@class,'title')]/span/text()",
            'required' => true,
        ),
        // 项目item_logo
        array(
            'name' => "item_logo",
            'selector' => "//div[contains(@class,'img')]/img/@src",
            'required' => true,
        ),
        // 项目item_brief
        array(
            'name' => "item_brief",
            'selector' => "//div[contains(@class,'info')]/p[4]/text()",
            'required' => true,
        ),
        // 项目item_area
        array(
            'name' => "item_area",
            'selector' => "//div[contains(@class,'info')]/p/a[contains(@class,'btn default')]/text()",
            'required' => true,
        ),
        // 项目item_CEO
        array(
            'name' => "item_CEO",
            'selector' => "//h4[contains(@class,'media-heading')]/text()",
            'required' => false,
        ),
        // 项目item_round
        array(
            'name' => "item_round",
            'selector' => "//table[contains(@class,'record-table')]//td[contains(@class,'td1')]/span/text()",
            'required' => false,
        ),
        // 项目item_website
        array(
            'name' => "item_website",
            'selector' => "//div[contains(@class,'info')]/p[3]/a/@href",
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
            'selector' => "//div[contains(@class,'portlet margin-top-30')]/p/text()",
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
    if($fieldname == 'item_id'){
        $itemids = explode('view-pv-',$data);
        $data = $itemids[1];
    }elseif ($fieldname == 'item_from'){
        $data = '新芽Newseed';
    }elseif ($fieldname == 'item_brief'){
        $data = strip_tags($data,"");
        $data = trim($data);
    }
    elseif ($fieldname == 'item_phone'){
        $data = '';
    }elseif ($fieldname == 'item_email'){
        $data = '';
    }elseif ($fieldname == 'item_from_website'){
        $data = $page['request']['url'];
    }elseif ($fieldname == 'item_round'){
        $data = strip_tags($data,"");
        $data = trim($data);
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

    }elseif ($fieldname == 'item_CEO'){
        $data = '';
    }
    elseif ($fieldname == 'item_address'){
        $itemaddrs = explode('详细地址：',$data);
        $data = $itemaddrs[1];
    }
    return $data;
};

$spider->start();

?>

