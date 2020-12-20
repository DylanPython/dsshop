<?php
/**
 * 微信公众号模板消息
 */

namespace App\Channels;
use App\Models\v1\NotificationLog;
use Carbon\Carbon;
use EasyWeChat\Factory;
use Illuminate\Notifications\Notification;

class WechatChannel
{
    private $config;
    private $app;
    private $information;
    private $miniweixin;
    public function __construct()
    {
        // 当配置过了微信小程序，通知一律跳转到小程序，否则跳H5
        $this->miniweixin = config('wechat.mini_program.default.app_id') ?? false;
        $this->config = config('wechat.official_account.default');
        $this->app = Factory::officialAccount($this->config);
        $this->information = config('notification.wechat');
    }
    /**
     * 发送指定的通知.
     *
     * @param  mixed $notifiable
     * @param  \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->invoice['parameter'];
        //配置了微信公众平台、wechat存在值、配置过模板ID
        if($this->config['app_id'] && $notifiable->wechat){
            if($this->information[$message['template']]){
                $identification=convertUnderline($message['template']);
                $this->$identification($notifiable,$message);
            }
        }
    }

    /**
     *  发货通知
     * @param $notifiable
     * @param $message
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function deliveryRelease($notifiable,$message){
        $data = [
            'template_id' => $this->information[$message['template']],
            'touser' => $notifiable->wechat,
            'data' => [
                'first' => '您购买的订单已经发货啦，正快马加鞭向您飞奔而去。',
                'keyword1' => $message['identification'],
                'keyword2' => $message['shipping_time'],
                'keyword3' => $message['dhl'],
                'keyword4' => $message['odd'],
                'keyword5' => $message['location']->name.' '.$message['location']->cellphone.' '.$message['location']->location.$message['location']->house,
                'remark' =>'请保持收件手机畅通！',
            ],
        ];
        if($this->miniweixin){
            $data['miniprogram']=[
                'appid' => $this->miniweixin,
                'pagepath' => 'pages/order/showOrder?id=' . $message['id'],
            ];
        }else{
            $data['url']= request()->root().'/h5/#'.'pages/order/showOrder?id=' . $message['id'];
        }
        //发送记录
        $send=$this->app->template_message->send($data);
        $NotificationLog =new NotificationLog();
        $NotificationLog->user_id = $message['user_id'];
        $NotificationLog->type = NotificationLog::NOTIFICATION_LOG_TYPE_MINIWEIXIN;
        $NotificationLog->msg = json_encode($data);
        $NotificationLog->feedback = json_encode($send);
        $NotificationLog->state = $send['errcode'] == 0 ? NotificationLog::NOTIFICATION_LOG_STATE_OK: NotificationLog::NOTIFICATION_LOG_STATE_ERROR;
        $NotificationLog->save();
    }

    /**
     *  订单支付成功
     * @param $notifiable
     * @param $message
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function finishPayment($notifiable,$message){
        $data = [
            'template_id' => $this->information[$message['template']],
            'touser' => $notifiable->wechat,
            'data' => [
                'first' => '恭喜您！购买的商品已支付成功，我们会尽快安排发货哦！么么哒！~~',
                'keyword1' => $message['identification'],
                'keyword2' => $message['name'],
                'keyword3' => sprintf("%01.2f",$message['total']/100),
                'keyword4' => '已支付',
                'keyword5' => $message['time'],
                'remark' =>'欢迎您的到来！',
            ],
        ];
        if($this->miniweixin){
            $data['miniprogram']=[
                'appid' => $this->miniweixin,
                'pagepath' => '/pages/order/showOrder?id=' . $message['id'],
            ];
        }else{
            $data['url']= request()->root().'/h5/#/pages/order/showOrder?id=' . $message['id'];
        }
        //发送记录
        $send=$this->app->template_message->send($data);
        $NotificationLog =new NotificationLog();
        $NotificationLog->user_id = $message['user_id'];
        $NotificationLog->type = NotificationLog::NOTIFICATION_LOG_TYPE_MINIWEIXIN;
        $NotificationLog->msg = json_encode($data);
        $NotificationLog->feedback = json_encode($send);
        $NotificationLog->state = $send['errcode'] == 0 ? NotificationLog::NOTIFICATION_LOG_STATE_OK: NotificationLog::NOTIFICATION_LOG_STATE_ERROR;
        $NotificationLog->save();
    }
}