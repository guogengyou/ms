<?php
/*
    public无需登录，即可访问：
        登录、登出、数据合法性校验 + 修改密码、注册(仅ms模块) + 邮箱验证码发送
    private需要登陆：JWT的发放
    
*/

namespace app\admin\controller;
use think\Controller;
use think\Loader;
use think\Db;
use think\Request;

class Login extends Controller            
{

    /**
     * 登入
     */
    public function index()
    {
        //$this->view->engine->layout(false);
        //echo '渲染登录页';
        return $this->fetch();              // 渲染登录页
    }

    //处理登录请求
    public function dologin() {
        
        $email = input('email');
        $password = input('password');
        $code = input('code');
        $x = Request::instance()->param();
        //var_dump($x);
        //return;
        $captcha = new \think\captcha\Captcha();  
        $result=$captcha->check($code);  
        if($result===false){  
            return $this->error('验证码错误');
        }
        
        //验证数据合法性：邮箱、密码
        //$c = controller(Common);
        if(self::check_email($email) == false || self::check_password($password) == false) { 
            return $this->error('数据错误登陆失败'); 
        }
        //md5 加盐
        $salt = config('salt');
        $password = md5( md5($password).$salt );

        $info = Db::table('admin')->where('email',$email)->find () ;
        if($info == null || $info['password'] != $password) {
            return $this->error('密码错误登陆失败'); 
        }
        
        else {
            // 设置Cookie 有效期为 2*24*60*60
            $token = self::getToken();
            cookie('token',$token,2*24*60*60);
            $x = cookie('token');
            //echo "login<br>";
            //var_dump($x);
            //echo "<br>";
            return $this->success('登陆成功', 'index/index');
        }
    }
    
    // 未登录时，修改密码（通过邮箱验证码）
    public function changepw() {
        // 发送邮件
        self::email();
        return $this->fetch();
    }
    public function dochangepw() {
        $code = input('code');
        $password = input('password');
        //校验数据合法性
        if(self::check_password($password) == false) { 
            return $this->error('数据错误登陆失败'); 
        }
        //md5 加盐
        $salt = config('salt');
        $password = md5( md5($password).$salt );

        // 操作数据库
        $info = Db::table('admin')->where('id',1)->find () ;
        if($code != $info['email_code']) {       
            return $this->error('验证码错误');
        }
        else {
            cookie('token', null);      // 清除旧密码颁发的token
            Db::table('admin')->where('id',1)->setField('password', $password);
            return $this->success('修改密码成功', 'login/index');
        }
    }


    //* 登出
    public function logout()
    {
        cookie('token', null);
        //echo "退出登录！<br>";
        //var_dump(cookie('token'));
        $this->success('退出成功', 'login/index');
    }
   
    // JWT
    private static $header=array(
        'alg'=>'SHA256', //生成signature的算法
        'typ'=>'JWT'  //类型
    );
    private static $payload= array();
    private function base64url_encode($plainText) {
        $base64 = base64_encode($plainText);
        $base64url = strtr($base64, '+/=', '-_,');
        return $base64url;  
    }
    private function base64url_decode($plainText) {
        $base64url = strtr($plainText, '-_,', '+/=');
        $base64 = base64_decode($base64url);
        return $base64;  
    }
    //使用HMAC生成信息摘要时所使用的密钥
    private function getToken(){
        
        $key = config('salt');
        $base64 = json_encode(self::$header);
        $baseencode = self::base64url_encode($base64);
        
        $email = input('email');
        self::$payload['email']= $email;
        self::$payload['auth']= "admin";
        self::$payload['time']= time()+2*24*60*60;      //过期时间 2天
        
        $basepayLoad64=json_encode(self::$payload);
        $base64payload = self::base64url_encode($basepayLoad64);
        //生成签名
        $input = $baseencode.".".$base64payload;
        $token = $this->signature($input,$key,self::$header['alg']);
        $token = self::base64url_encode($token);
        $token = $input.".".$token;
        return $token;
    }
    private function signature($input,$key,$alg){
        $base64 = hash_hmac($alg,$input,$key,true);
        return $base64;
    }
    
    //数据合法性检验：邮箱，学号，密码--- 用于邮箱验证、登录、注册、修改密码
    //邮箱格式校验
    public function check_email($val){
        $email = trim($val);     // 验证输入是否为空串,先去除两侧空格
        if(strlen($email) == 0) 
            return false;     
            
	    //$pattern = '/^[0-9]+@(stu\.)*pku\.edu\.cn$/';     // pku邮箱
	    $pattern = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,})$/'; 
	    if(preg_match($pattern,$email) == 0)
	        return false; 
	        
	    return true;
    }
    //密码格式校验
    public function check_password($val){
        $pw = trim($val);       // 验证输入是否为空串,先去除两侧空格
        if(strlen($pw) == 0) 
            return false;     
         //{4,20}
        $pattern = '/^[A-Za-z0-9]{4,10}$/';
	    if(preg_match($pattern,$val) == 0)
	        return false; 
	        
	    return true;
    }
    
    //发送邮箱验证码，并将验证码存入数据库
    public function email() {
        //admin模块的邮箱不能通过前端修改，只能改数据库，所以写死。
        //ms模块需要前端或JWT提供email
        
        $email = "2001210254@stu.pku.edu.cn";
        /*$email = input('email');
        //解封web token，获取信息
        $c = controller(Common);
        $payload = $c->token_payload();
        $student_id = $payload->student_id;
        */
        //邮箱格式 数据合法性验证
        $c = controller(Login);
        if($c->check_email($email) == false) {      
            return $this->error('邮箱格式错误');
        }
        
        //生成验证码、并发送邮件
        $code = md5(time());
        $email_code = substr($code, 0, 4);
        $test1 = "【msqg】邮箱验证码： ";
        $test2 = "，请勿泄露给其他人。"; 
        //Db::table('user')->where('student_id',$student_id)->setField('email_code', $email_code);
        Db::table('admin')->where('id',1)->setField('email_code', $email_code);
        
        $toemail=$email;
        $name='admin';
        $subject='邮箱验证码';
        $content=' '.$test1.$email_code.$test2;
        self::send_mail($toemail,$name,$subject,$content);
        //dump(self::send_mail($toemail,$name,$subject,$content));
        //echo "邮箱验证码已发送";
    }
    /**
     * 系统邮件发送函数
     * @param string $tomail 接收邮件者邮箱
     * @param string $name 接收邮件者名称
     * @param string $subject 邮件主题
     * @param string $body 邮件内容
     * @param string $attachment 附件列表
     * @return boolean
     * @author static7 <static7@qq.com>
     */
    private function send_mail($tomail, $name, $subject = '', $body = '', $attachment = null) {
        //实例化PHPMailer对象；若没有use PHPMailer\PHPMailer\PHPMailer，则需要写为 new \PHPMailer\PHPMailer\PHPMailer();
        // $mail = new PHPMailer();
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
        $mail->IsSMTP();                    // 设定使用SMTP服务
        $mail->SMTPDebug = 1;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
        $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
        $mail->SMTPSecure = 'ssl';          // 使用安全协议
        $mail->Host = "smtp.163.com"; // SMTP 服务器
        $mail->Port = 465;                  // SMTP服务器的端口号
        $mail->Username = "18513116992@163.com";    // SMTP服务器用户名
        $mail->Password = "XFUFYQECXJHBLCFT";     // SMTP服务器密码
        $mail->SetFrom('18513116992@163.com', '18513116992');
        $replyEmail = '';                   //留空则为发件人EMAIL
        $replyName = '';                    //回复名称（留空则为发件人名称）
        $mail->AddReplyTo($replyEmail, $replyName);
        $mail->Subject = $subject;
        $mail->MsgHTML($body);
        $mail->AddAddress($tomail, $name);
        if (is_array($attachment)) { // 添加附件
            foreach ($attachment as $file) {
                is_file($file) && $mail->AddAttachment($file);
            }
        }
        //return $mail->Send() ? true : $mail->ErrorInfo;
        $mail->Send();
    }
}



