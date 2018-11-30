<?php
namespace app\admin\controller;

use app\common\controller\Common;
use app\common\model\Admin;
use think\Db;
use think\facade\Session;
use think\Validate;

class Auth extends Common
{
    protected $middleware = [];

    /**
     * 管理员后台登陆
     * @return \think\response\Json|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login()
    {
        if (Session::has('admin')) {
            $this->redirect('admin/index/index');
        }
        if ($this->request->isPost()) {
            $data = request()->post();
            $vaildate = new Validate([
                'username' => 'require',
                'password' => 'require',
                'captcha' => 'require|captcha'
            ], [], [
                'username' => '用户名',
                'password' => '密码',
                'captcha' => '验证码',
            ]);
            if (!$vaildate->check($data)){
                return rJson(false, $vaildate->getError());
            }

            $admin = Admin::where(['username' => $data['username']])->find();
            if (empty($admin)) {
                return rJson(false, '账号不存在');
            }
            if ($admin->password != (md5(md5($data['password']) . md5($admin->salt)))){
                return rJson(false, '密码错误');
            }
            $ip = request()->ip();

            $admin->login_ip = $ip;
            $admin->login_time = time();
            $admin->save();

            Session::set('admin', $admin->toArray());
            return rJson(true, '登录成功');
        }

        return view('admin');
    }


    /**
     * 后台管理退出
     * @return \think\Response|\think\response\Json|\think\response\Jsonp|\think\response\Redirect|\think\response\View|\think\response\Xml
     */
    public function logout()
    {
        $type = $this->request->param('type', 'admin');
        if ($type == 'admin') {
            Session::delete('admin');
            if (!Session::has('admin')) {
                return rJson(true, '退出成功！');
            } else {
                return rJson(false, '退出失败！');
            }
        }
    }

    /**
     * 发送验证码
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sendCode()
    {
        $post = $this->request->param();
        $vaildate = new Validate([
            'email' => 'require|email',
        ], [], [
            'email' => '邮箱',
        ]);
        if (!$vaildate->check($post)) {
            return rJson(false, $vaildate->getError());
        }
        //查询商户是否存在
        $res = Merchant::get([
            'email' => $post['email']
        ]);
        if (!$res) {
            return rJson(false, '邮箱不存在');
        }

        $req = $this->sendEmailCode($post['email'], 2);

        if ($req['err_code'] == 0) {
            return rJson(true, '发送成功');
        } else {
            return rJson(false, $req['err_msg']);
        }
    }

    /**
     * 发送验证码
     * @param $email
     * @param $type 1 提现  2 重置密码
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function sendEmailCode($email, $type){
        $code = rand(10000, 99999);
        $time_out = 60 * 10;
        $time = time();

        $sms_code = EmailCode::where(['email' => $email, 'type' => $type])->order('id', 'desc')->find();
        if ($sms_code){
            if ($sms_code->expire_at > $time){
                if (($sms_code->created_at + 60) > $time){
                    //限制发送间隔一分钟
                    return [
                        'err_code' => -1,
                        'err_msg' => '发送太频繁，请在 '.(($sms_code->created_at + 60) - $time).' 秒后重新发送！'
                    ];
                }else{
                    //没有过期就更新
                    $sms_code->code = $code;
                    $sms_code->created_at = $time;
                    $sms_code->expire_at = $time + $time_out;
                    $sms_code->save();
                }
            }else{
                //过期，删除并添加
                $sms_code->delete();
                EmailCode::create([
                    'email' => $email,
                    'code'  => $code,
                    'type'  => $type,
                    'created_at' => $time,
                    'expire_at' => $time + $time_out
                ]);
            }
        }else{
            //没有就添加
            EmailCode::create([
                'email' => $email,
                'code'  => $code,
                'type'  => $type,
                'created_at' => $time,
                'expire_at' => $time + $time_out
            ]);
        }

        $config_list = Db::name('system_config')->where(['group_id' => 3])->select();
        $config = [];
        foreach ($config_list as $item) {
            $config[$item['config_name']] = $item['config_value'];
        }

        $subject = '您当前重置密码的验证码是【'.$code.'】';
        $content = "您正在重置你的账号密码，验证码为【{$code}】，10分钟内有效，请不要告诉他人！";

        try{
            $mail = new PHPMailer();           //实例化PHPMailer对象
            $mail->CharSet = 'UTF-8';           //设定邮件编码，默认ISO-8859-1，如果发中文此项必须设置，否则乱码
            $mail->IsSMTP();                    // 设定使用SMTP服务
            $mail->SMTPDebug = 0;               // SMTP调试功能 0=关闭 1 = 错误和消息 2 = 消息
            $mail->SMTPAuth = true;             // 启用 SMTP 验证功能
            $mail->SMTPSecure = 'ssl';          // 使用安全协议
            $mail->Host = $config['SMTP_HOST']; // SMTP 服务器
            $mail->Port = $config['SMTP_PORT'];                  // SMTP服务器的端口号
            $mail->Username = $config['SMTP_ACCOUNT'];    // SMTP服务器用户名
            $mail->Password = $config['SMTP_PWD'];     // SMTP服务器密码
            $mail->SetFrom($config['SMTP_ACCOUNT']);
            $replyEmail = '';                   //留空则为发件人EMAIL
            $replyName = '';                    //回复名称（留空则为发件人名称）
            $mail->AddReplyTo($replyEmail, $replyName);
            $mail->Subject = $subject;  //标题
            $mail->MsgHTML($content);  //内容
            $mail->AddAddress($email); //目标邮件
            $req = $mail->Send();
            if (!$req){
                return [
                    'err_code' => -1,
                    'err_msg' => '发送失败('. $mail->ErrorInfo.')'
                ];
            }
            return [
                'err_code' => 0,
                'err_msg' => '发送成功'
            ];
        }catch (\Exception $e){
            return [
                'err_code' => -1,
                'err_msg' => '发送失败('. $e->getMessage().')'
            ];
        }
    }
}