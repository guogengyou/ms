<?php

/**
 * 抢购首页：
 * 
 * 
 * 由父类Common的构造函数自动检查登录
 */
 
//必须要有，否则报错：  控制器不存在 :app\index\controller\Index 
namespace app\ms\controller;  
use think\Controller;               // 这是一对
use think\Loader;
use think\Db;
use think\Request;

//use PHPMailer\PHPMailer\PHPMailer;
//class Index extends controller            // 登陆验证，拦截功能
class Index extends Common            // 登陆验证，拦截功能
{  
    public function index(){
        //$info = Db::table('user')->select();
        
        $info = Db('goods')->select();
        $this->assign('lists', $info);
        
        return $this->fetch('');
        
    }
    // 加载产品信息title、detail，返回Json格式
    public function goods_load(){
        $info = Db::table('goods')->where('id', 1)->find();
        $obj = new \stdClass();   
        $obj->title = $info['title'];       //$info['email_code']
        $obj->detail = $info['detail'];
        $x = json_encode($obj);
        return $x;
        //return json_decode($x);
    }
    
    // 用户信息
    public function userinfo(){
        $c = controller(Common);
        $payload = $c->token_payload();
        $email = $payload->email;

        $info = Db::table('user')->where('email',$email)->select();
        $this->assign('lists', $info);
        
        
        return $this->fetch('userinfo');
        
    }    
    
    // 修改用户信息
    public function change_userinfo() {
        //发送邮箱验证码
        $c = controller(Login);
        $c->email();
        return $this->fetch();
    }
    public function dochange_userinfo() {
        $code = input('code');
        $newemail = input('email');
        $name = input('name');
        $password = input('password');

        // 解封web token，获取信息
        $c = controller(Common);
        $payload = $c->token_payload();
        $email = $payload->email;
        
        // 操作数据库
        $info = Db::table('user')->where('email',$email)->find () ;
        
        if($info == null || $code != $info['email_code']) {       
            return $this->error('验证码或token邮箱错误');
        }
        else {
            cookie('token', null);      // 清除旧密码颁发的token
            if($newemail != null){
                Db::table('user')->where('email',$email)->setField('password', $newemail);
            }
            if($name != null){
                Db::table('user')->where('email',$email)->setField('password', $name);
            }
            if($password != null){
                //md5 加盐
                $salt = config('salt');
                $password = md5( md5($password).$salt );
                Db::table('user')->where('email',$email)->setField('password', $password);
            }
            return $this->success('修改成功', 'login/index');
        }
    }
    
    // 抢购商品, 调用go服务器写mysql
        function request_by_socket($remote_server,$remote_path,$post_string,$port = 80,$timeout = 30) {
        
            $socket = fsockopen($remote_server, $port, $errno, $errstr, $timeout);
            if (!$socket) die("$errstr($errno)");
            fwrite($socket, "POST $remote_path HTTP/1.0");
            fwrite($socket, "User-Agent: Socket Example");
            fwrite($socket, "HOST: $remote_server");
                        fwrite($socket, "Content-type: application/x-www-form-urlencoded");
            fwrite($socket, "Content-length: " . (strlen($post_string) + 8) . "");
            fwrite($socket, "Accept:*/*");
            fwrite($socket, "");
            fwrite($socket, "mypost=$post_string");
            fwrite($socket, "");
            $header = "";
            while ($str = trim(fgets($socket, 4096))) {
                $header .= $str;
            }
            $data = "";
            while (!feof($socket)) {
            $data .= fgets($socket, 4096);
            }
            return $data;
        }
        

    public function qg_goods() {
        //检查图形验证码
        $code = input('code');
        $captcha = new \think\captcha\Captcha();  
        $result=$captcha->check($code);  
        if($result===false){  
            return '验证码错误';
        }
        //验证时间
        $time = strtotime("2021-05-01 14:00:00");
        if(time() <  $time){
            return '未到时间！';
        }
        
        // 获取身份
        $c = controller(Common);
        $payload = $c->token_payload();
        $email = $payload->email;
        $info = Db::table('ms_orders')->where('email', $email)->find();
        if($info != null){
            return '每人只能抢一次！';
        }
 
        // 查询redis数据
        $redis = new \Redis();
        $redis->connect('127.0.0.1', 6379);
        $discount_number = $redis->get("discount_number");
        $stock_number = $redis->get("stock_number");
        
        // 处理
        if($stock_number == 0){
            //  ****调用go服务器进行写 状态0
            self::sock_get($email,'0');
            return '库存已被抢光！';
        }
        else if($discount_number == 0){
            $stock_number = $stock_number - 1;
            $redis->set("stock_number", $stock_number);
            // ****调用go服务器进行写
            self::sock_get($email,'2');
            return '恭喜！您抢到了优惠价';
        }
        else{
            $discount_number = $discount_number - 1;
            $redis->set("discount_number", $discount_number);
            // ****调用go服务器进行写
            self::sock_get($email,'1');
            return '恭喜！您抢到了特惠价';            
        }
    }
    public function qgxx(){
        $c = controller(Common);
        $payload = $c->token_payload();
        $email = $payload->email; 
        $info = Db::table('ms_orders')->where('email', $email)->select();
        
        if($info == null){      // 未找到订单
            return $this->error('您未参与抢购');
        }
        
        $this->assign('lists', $info);
        return $this->fetch('');
    }
    
    // fsocket模拟get提交
    private function sock_get($email, $order_status){
        $url = "http://123.56.234.253:4444/producer";
        //print_r(parse_url($url));// 解析 URL，返回其组成部分
        $data = array(
            'email' => $email,
            'order_status' => $order_status
        );
        $query_str = http_build_query($data);// http_build_query()函数的作用是使用给出的关联（或下标）数组生成一个经过 URL-encode 的请求字符串
    
        $info = parse_url($url);
        //var_dump($info);
        //return;
        $fp = fsockopen($info["host"],4444,$errno,$errstr,30);
        $head = "GET " . $info['path'] . '?' . $query_str . " HTTP/1.0\r\n";        //有路径
        $head .= "Host: " . $info['host'] . "\r\n";
        $head .= "\r\n";
        $write = fputs($fp,$head);
        while(!feof($fp)){
            $line = fread($fp,4096);
        }
    }

}