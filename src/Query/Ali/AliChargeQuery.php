<?php
namespace Payment\Query\Ali;

use Payment\Common\Ali\AliBaseStrategy;
use Payment\Common\Ali\Data\Query\ChargeQueryData;
use Payment\Common\PayException;
use Payment\Config;
use Payment\Utils\ArrayUtil;

/**
 * 统一收单线下交易查询
 * @link      https://www.gitbook.com/book/helei112g1/payment-sdk/details
 * @link      https://helei112g.github.io/
 *
 * Class AliChargeQuery
 * @package Payment\Query\Ali
 */
class AliChargeQuery extends AliBaseStrategy
{
    protected static $method = 'alipay.trade.query';

    /**
     * 返回数据构建类
     * @return string
     */
    public function getBuildDataClass()
    {
        $this->config->method = static::$method;
        return ChargeQueryData::class;
    }

    /**
     * 请求后得到的返回数据
     * @param array $data
     * @return array|mixed
     * @throws PayException
     */
    protected function retData(array $data)
    {
        $data = parent::retData($data); // TODO: Change the autogenerated stub

        try {
            $ret = $this->sendReq($data);
        } catch (PayException $e) {
            throw $e;
        }

        if ($this->config->returnRaw) {
            $ret['channel'] = Config::ALI_CHARGE;
            return $ret;
        }

        return $this->createBackData($ret);
    }

    /**
     * 处理支付宝返回的数据，统一处理后返回
     * @param array $data  支付宝返回的数据
     * @return array
     * @author helei
     */
    protected function createBackData(array $data)
    {
        // 新版本
        if ($data['code'] !== '10000') {
            return [
                'is_success'    => 'F',
                'error' => $data['sub_msg'],
                'channel'   => Config::ALI_CHARGE,
            ];
        }

        // 正确情况
        $retData = [
            'is_success'    => 'T',
            'response'  => [
                'channel'   => Config::ALI_CHARGE,
                'transaction_id'   => $data['trade_no'],// 支付宝交易号
                'order_no'   => $data['out_trade_no'],// 商家订单号
                'logon_id'   => $data['buyer_logon_id'],// 买家支付宝账号
                'trade_state'   => $this->getTradeStatus($data['trade_status']),
                'amount'   => $data['total_amount'],
                'receipt_amount' => $data['receipt_amount'],// 实收金额，单位为元，两位小数。

                'pay_amount'    => ArrayUtil::get($data, 'buyer_pay_amount'),// 买家实付金额，单位为元
                'point_amount' => ArrayUtil::get($data, 'point_amount'),// 使用集分宝支付的金额
                'invoice_amount' => ArrayUtil::get($data, 'invoice_amount'),// 交易中用户支付的可开具发票的金额，单位为元，两位小数
                'time_end'   => ArrayUtil::get($data, 'send_pay_date'),// 	本次交易打款给卖家的时间
                'store_id' => ArrayUtil::get($data, 'store_id'),
                'terminal_id' => ArrayUtil::get($data, 'terminal_id'),
                'store_name' => ArrayUtil::get($data, 'store_name'),
                'buyer_id'   => ArrayUtil::get($data, 'buyer_user_id'),
                'fund_bill_list' => ArrayUtil::get($data, 'fund_bill_list', []),// 支付成功的各个渠道金额信息
            ],
        ];

        return $retData;
    }
}
